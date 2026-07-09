<?php
/**
 * Copyright © MageDevGroup. All rights reserved.
 */
declare(strict_types=1);

namespace MageDevGroup\AdminScimGoogle\Test\Unit\Model\Normalization;

use MageDevGroup\AdminScim\Api\RequestNormalizerInterface;
use MageDevGroup\AdminScimGoogle\Model\Normalization\GoogleRequestNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class GoogleRequestNormalizerTest extends TestCase
{
    /**
     * @var GoogleRequestNormalizer
     */
    private GoogleRequestNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new GoogleRequestNormalizer();
    }

    public function testImplementsExtensionPointInterface(): void
    {
        self::assertInstanceOf(RequestNormalizerInterface::class, $this->normalizer);
    }

    public function testStandardUserPayloadPassesThroughUnchanged(): void
    {
        $payload = [
            'userName' => 'jane@example.com',
            'name' => ['givenName' => 'Jane', 'familyName' => 'Doe'],
            'active' => true,
            'emails' => [['value' => 'jane@example.com', 'type' => 'work', 'primary' => true]],
        ];

        self::assertSame(
            $payload,
            $this->normalizer->normalize(RequestNormalizerInterface::RESOURCE_USER, 'POST', $payload)
        );
    }

    public function testNativeBooleanActiveIsLeftUntouched(): void
    {
        $result = $this->normalizer->normalize(
            RequestNormalizerInterface::RESOURCE_USER,
            'POST',
            ['userName' => 'jane@example.com', 'active' => false]
        );

        self::assertFalse($result['active']);
    }

    #[DataProvider('stringifiedActiveProvider')]
    public function testStringifiedTopLevelActiveIsCoercedToBool(string $given, bool $expected): void
    {
        $result = $this->normalizer->normalize(
            RequestNormalizerInterface::RESOURCE_USER,
            'PUT',
            ['userName' => 'jane@example.com', 'active' => $given]
        );

        self::assertSame($expected, $result['active']);
    }

    /**
     * @return array<string,array{0:string,1:bool}>
     */
    public static function stringifiedActiveProvider(): array
    {
        return [
            'true' => ['true', true],
            'TRUE cased' => ['TRUE', true],
            'one' => ['1', true],
            'false' => ['false', false],
            'False padded' => [' False ', false],
            'zero' => ['0', false],
        ];
    }

    public function testUnrecognizedStringActiveIsLeftAsIsForCoreToValidate(): void
    {
        $result = $this->normalizer->normalize(
            RequestNormalizerInterface::RESOURCE_USER,
            'POST',
            ['userName' => 'jane@example.com', 'active' => 'maybe']
        );

        self::assertSame('maybe', $result['active']);
    }

    public function testPayloadWithoutActiveIsUnchanged(): void
    {
        $payload = ['userName' => 'jane@example.com'];

        self::assertSame(
            $payload,
            $this->normalizer->normalize(RequestNormalizerInterface::RESOURCE_USER, 'POST', $payload)
        );
    }

    public function testPatchTargetedActiveOperationIsCoerced(): void
    {
        // Google suspends a user with a targeted `active` replace.
        $result = $this->normalizer->normalize(
            RequestNormalizerInterface::RESOURCE_USER,
            'PATCH',
            ['Operations' => [['op' => 'replace', 'path' => 'active', 'value' => 'false']]]
        );

        self::assertFalse($result['Operations'][0]['value']);
    }

    public function testPatchPathlessActiveObjectIsCoerced(): void
    {
        $result = $this->normalizer->normalize(
            RequestNormalizerInterface::RESOURCE_USER,
            'PATCH',
            ['Operations' => [['op' => 'replace', 'value' => ['active' => 'false']]]]
        );

        self::assertFalse($result['Operations'][0]['value']['active']);
    }

    public function testPatchPathlessActiveIsCoercedWhileSiblingAttributesSurvive(): void
    {
        $result = $this->normalizer->normalize(
            RequestNormalizerInterface::RESOURCE_USER,
            'PATCH',
            ['Operations' => [[
                'op' => 'replace',
                'value' => ['givenName' => 'Jane', 'familyName' => 'Doe', 'active' => 'false'],
            ]]]
        );

        $value = $result['Operations'][0]['value'];
        self::assertFalse($value['active']);
        self::assertSame('Jane', $value['givenName']);
        self::assertSame('Doe', $value['familyName']);
    }

    public function testPatchLowercaseOperationsKeyIsHandled(): void
    {
        $result = $this->normalizer->normalize(
            RequestNormalizerInterface::RESOURCE_USER,
            'PATCH',
            ['operations' => [['op' => 'replace', 'path' => 'Active', 'value' => 'true']]]
        );

        self::assertTrue($result['operations'][0]['value']);
    }

    public function testPatchLeavesOtherOperationsUntouched(): void
    {
        $payload = [
            'Operations' => [
                ['op' => 'replace', 'path' => 'userName', 'value' => 'jane@example.com'],
                ['op' => 'replace', 'path' => 'active', 'value' => true],
            ],
        ];

        self::assertSame(
            $payload,
            $this->normalizer->normalize(RequestNormalizerInterface::RESOURCE_USER, 'PATCH', $payload)
        );
    }

    public function testMalformedPatchOperationsAreNotTouched(): void
    {
        $payload = ['Operations' => 'not-a-list'];

        self::assertSame(
            $payload,
            $this->normalizer->normalize(RequestNormalizerInterface::RESOURCE_USER, 'PATCH', $payload)
        );
    }

    public function testNonArrayOperationEntriesSurvive(): void
    {
        $payload = ['Operations' => ['garbage', ['op' => 'replace', 'path' => 'active', 'value' => 'false']]];

        $result = $this->normalizer->normalize(RequestNormalizerInterface::RESOURCE_USER, 'PATCH', $payload);

        self::assertSame('garbage', $result['Operations'][0]);
        self::assertFalse($result['Operations'][1]['value']);
    }

    public function testGroupPayloadPassesThroughUnchanged(): void
    {
        $payload = [
            'displayName' => 'Admins',
            'members' => [['value' => '1'], ['value' => '2']],
            'active' => 'true',
        ];

        self::assertSame(
            $payload,
            $this->normalizer->normalize(RequestNormalizerInterface::RESOURCE_GROUP, 'POST', $payload)
        );
    }

    public function testNonPatchOperationSkipsOperationsWalk(): void
    {
        $payload = ['Operations' => [['op' => 'replace', 'path' => 'active', 'value' => 'false']]];

        // A POST is not a PatchOp; the Operations array is data, not ops — leave it.
        self::assertSame(
            $payload,
            $this->normalizer->normalize(RequestNormalizerInterface::RESOURCE_USER, 'POST', $payload)
        );
    }
}
