<?php
/**
 * Copyright © MageDevGroup. All rights reserved.
 */
declare(strict_types=1);

namespace MageDevGroup\AdminScimGoogle\Test\Unit\Compatibility;

use MageDevGroup\AdminScim\Api\RequestNormalizerInterface;
use MageDevGroup\AdminScim\Model\Normalization\RequestNormalizerChain;
use MageDevGroup\AdminScimGoogle\Model\Normalization\GoogleRequestNormalizer;
use PHPUnit\Framework\TestCase;

/**
 * Task 4 compatibility check.
 *
 * Drives the whole admin-user provisioning lifecycle (create → read/filter →
 * update → deactivate via `active:false` → group) with Google-shaped, stubbed
 * SCIM request bodies, run through the *real* {@see RequestNormalizerChain} (the
 * shared open/closed seam) with only the Google normalizer registered — proving
 * the plugin plugs in and produces strict-RFC output the core can handle.
 *
 * The second half is the shared-seam regression guard: Google is a strict no-op
 * on payloads that belong to `admin-scim-okta` / `admin-scim-azure`, so adding it
 * to the chain cannot corrupt what those providers normalize. (Those providers'
 * classes are intentionally not imported here — this module doesn't depend on
 * them; the guarantee that matters is that Google leaves their payloads
 * byte-identical, which this asserts directly.)
 */
class GoogleLifecycleCompatibilityTest extends TestCase
{
    /**
     * @var RequestNormalizerChain
     */
    private RequestNormalizerChain $chain;

    protected function setUp(): void
    {
        // The exact wiring etc/di.xml declares: the Google normalizer merged into
        // admin-scim's chain.
        $this->chain = new RequestNormalizerChain([new GoogleRequestNormalizer()]);
    }

    public function testCreateUserCoercesStringifiedActiveToStrictRfc(): void
    {
        $body = [
            'schemas' => ['urn:ietf:params:scim:schemas:core:2.0:User'],
            'userName' => 'jane@example.com',
            'name' => ['givenName' => 'Jane', 'familyName' => 'Doe'],
            'emails' => [['value' => 'jane@example.com', 'type' => 'work', 'primary' => true]],
            'active' => 'true',
        ];

        $result = $this->chain->apply(RequestNormalizerInterface::RESOURCE_USER, 'POST', $body);

        self::assertTrue($result['active'], 'Google create must land a native bool for the core.');
        // Everything else is standard SCIM and must survive untouched.
        self::assertSame($body['userName'], $result['userName']);
        self::assertSame($body['name'], $result['name']);
        self::assertSame($body['emails'], $result['emails']);
        self::assertSame($body['schemas'], $result['schemas']);
    }

    public function testReadFilterCarriesNoBodyAndPassesThrough(): void
    {
        // A GET (list/filter by userName) has no write body; the seam is a no-op.
        self::assertSame(
            [],
            $this->chain->apply(RequestNormalizerInterface::RESOURCE_USER, 'GET', [])
        );
    }

    public function testUpdateUserReplacesStringifiedActive(): void
    {
        $body = [
            'schemas' => ['urn:ietf:params:scim:schemas:core:2.0:User'],
            'userName' => 'jane@example.com',
            'name' => ['givenName' => 'Jane', 'familyName' => 'Roe'],
            'active' => 'false',
        ];

        $result = $this->chain->apply(RequestNormalizerInterface::RESOURCE_USER, 'PUT', $body);

        self::assertFalse($result['active']);
        self::assertSame('Roe', $result['name']['familyName']);
    }

    public function testDeactivateViaTargetedActivePatch(): void
    {
        // Google suspends a user with a targeted `active` replace.
        $body = [
            'schemas' => ['urn:ietf:params:scim:api:messages:2.0:PatchOp'],
            'Operations' => [['op' => 'replace', 'path' => 'active', 'value' => 'false']],
        ];

        $result = $this->chain->apply(RequestNormalizerInterface::RESOURCE_USER, 'PATCH', $body);

        self::assertFalse($result['Operations'][0]['value']);
    }

    public function testDeactivateViaPathlessActivePatch(): void
    {
        // The path-less object form some Google connectors emit.
        $body = [
            'schemas' => ['urn:ietf:params:scim:api:messages:2.0:PatchOp'],
            'Operations' => [['op' => 'replace', 'value' => ['active' => 'false']]],
        ];

        $result = $this->chain->apply(RequestNormalizerInterface::RESOURCE_USER, 'PATCH', $body);

        self::assertFalse($result['Operations'][0]['value']['active']);
    }

    public function testReactivateViaTargetedActivePatch(): void
    {
        $body = ['Operations' => [['op' => 'replace', 'path' => 'active', 'value' => 'true']]];

        $result = $this->chain->apply(RequestNormalizerInterface::RESOURCE_USER, 'PATCH', $body);

        self::assertTrue($result['Operations'][0]['value']);
    }

    public function testGroupCreatePassesThroughUnchanged(): void
    {
        $body = [
            'schemas' => ['urn:ietf:params:scim:schemas:core:2.0:Group'],
            'displayName' => 'Administrators',
            'members' => [['value' => '1'], ['value' => '2']],
        ];

        self::assertSame(
            $body,
            $this->chain->apply(RequestNormalizerInterface::RESOURCE_GROUP, 'POST', $body)
        );
    }

    public function testGroupMembershipPatchPassesThroughUnchanged(): void
    {
        // Google's group-member add/remove is standard RFC PatchOp — untouched.
        $body = [
            'schemas' => ['urn:ietf:params:scim:api:messages:2.0:PatchOp'],
            'Operations' => [
                ['op' => 'add', 'path' => 'members', 'value' => [['value' => '3']]],
                ['op' => 'remove', 'path' => 'members[value eq "2"]'],
            ],
        ];

        self::assertSame(
            $body,
            $this->chain->apply(RequestNormalizerInterface::RESOURCE_GROUP, 'PATCH', $body)
        );
    }

    /**
     * The full lifecycle applied in sequence keeps the same identity coherent —
     * a light end-to-end smoke over the seam.
     */
    public function testFullLifecycleInSequence(): void
    {
        $created = $this->chain->apply(
            RequestNormalizerInterface::RESOURCE_USER,
            'POST',
            ['userName' => 'sam@example.com', 'active' => 'true']
        );
        self::assertTrue($created['active']);

        $updated = $this->chain->apply(
            RequestNormalizerInterface::RESOURCE_USER,
            'PUT',
            ['userName' => 'sam@example.com', 'active' => 'true', 'title' => 'Manager']
        );
        self::assertTrue($updated['active']);
        self::assertSame('Manager', $updated['title']);

        $deactivated = $this->chain->apply(
            RequestNormalizerInterface::RESOURCE_USER,
            'PATCH',
            ['Operations' => [['op' => 'replace', 'path' => 'active', 'value' => 'false']]]
        );
        self::assertFalse($deactivated['Operations'][0]['value']);
    }

    /**
     * Shared-seam regression guard: an Azure flat-extension PATCH belongs to
     * `admin-scim-azure`; Google must not touch it, so Azure's normalizer sees it
     * intact in the chain.
     */
    public function testAzureFlatExtensionPatchIsLeftForAzure(): void
    {
        $body = [
            'Operations' => [[
                'op' => 'replace',
                'path' => 'urn:ietf:params:scim:schemas:extension:enterprise:2.0:User:department',
                'value' => 'Sales',
            ]],
        ];

        self::assertSame(
            $body,
            $this->chain->apply(RequestNormalizerInterface::RESOURCE_USER, 'PATCH', $body)
        );
    }

    /**
     * Shared-seam regression guard: Azure's group-member remove-by-value — Google
     * leaves the Group resource entirely alone.
     */
    public function testAzureGroupMemberRemoveByValueIsLeftForAzure(): void
    {
        $body = [
            'Operations' => [[
                'op' => 'remove',
                'path' => 'members',
                'value' => [['value' => '42']],
            ]],
        ];

        self::assertSame(
            $body,
            $this->chain->apply(RequestNormalizerInterface::RESOURCE_GROUP, 'PATCH', $body)
        );
    }

    /**
     * Shared-seam regression guard: an Okta payload that already carries a native
     * boolean `active` passes through Google byte-identical.
     */
    public function testOktaNativeBooleanUserIsUnchanged(): void
    {
        $body = [
            'userName' => 'okta.user@example.com',
            'active' => false,
            'name' => ['givenName' => 'Ok', 'familyName' => 'Ta'],
        ];

        self::assertSame(
            $body,
            $this->chain->apply(RequestNormalizerInterface::RESOURCE_USER, 'PUT', $body)
        );
    }

    /**
     * The Google normalizer is idempotent over the whole seam: re-applying it to
     * already-normalized output is a fixpoint, so re-ordering or re-running the
     * chain cannot drift the payload.
     */
    public function testChainIsIdempotentAcrossLifecycle(): void
    {
        $cases = [
            [RequestNormalizerInterface::RESOURCE_USER, 'POST', ['userName' => 'a', 'active' => 'true']],
            [RequestNormalizerInterface::RESOURCE_USER, 'PATCH',
                ['Operations' => [['op' => 'replace', 'path' => 'active', 'value' => 'false']]]],
            [RequestNormalizerInterface::RESOURCE_GROUP, 'POST',
                ['displayName' => 'X', 'members' => [['value' => '1']]]],
        ];

        foreach ($cases as [$resource, $operation, $body]) {
            $once = $this->chain->apply($resource, $operation, $body);
            $twice = $this->chain->apply($resource, $operation, $once);
            self::assertSame($once, $twice, sprintf('%s %s is not a fixpoint.', $operation, $resource));
        }
    }
}
