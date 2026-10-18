<?php
/**
 * Copyright © DMLab. All rights reserved.
 * Licensed under the Open Software License version 3.0 (OSL-3.0). See LICENSE.
 */
declare(strict_types=1);

namespace DmLab\AdminScimGoogle\Test\Unit\Model\Google;

use Magento\Framework\Escaper;
use DmLab\AdminScim\Model\Discovery\EndpointUrlBuilder;
use DmLab\AdminScimGoogle\Model\Google\ScimSetupInfo;
use PHPUnit\Framework\TestCase;

/**
 * The setup surface must render the actual SCIM endpoint (from the core's URL
 * builder, HTML-escaped) plus only the Google behaviour Google documents.
 */
class ScimSetupInfoTest extends TestCase
{
    private const ENDPOINT = 'https://magento.example/admin-scim/v2';

    /** @var EndpointUrlBuilder */
    private EndpointUrlBuilder $urlBuilder;

    /** @var Escaper */
    private Escaper $escaper;

    /** @var ScimSetupInfo */
    private ScimSetupInfo $setupInfo;

    protected function setUp(): void
    {
        $this->urlBuilder = $this->createStub(EndpointUrlBuilder::class);
        $this->urlBuilder->method('baseUrl')->willReturn(self::ENDPOINT);

        $this->escaper = $this->createStub(Escaper::class);
        $this->escaper->method('escapeHtml')->willReturnArgument(0);

        $this->setupInfo = new ScimSetupInfo($this->urlBuilder, $this->escaper);
    }

    public function testEndpointUrlComesFromTheCoreBuilder(): void
    {
        self::assertSame(self::ENDPOINT, $this->setupInfo->getEndpointUrl());
    }

    public function testHtmlRendersTheEndpointAsEndpointUrl(): void
    {
        $html = $this->setupInfo->getHtml();

        self::assertStringContainsString(self::ENDPOINT, $html);
        self::assertStringContainsString('Endpoint URL', $html);
    }

    public function testHtmlEscapesEveryPhraseThroughTheEscaper(): void
    {
        $escaper = $this->createMock(Escaper::class);
        $escaper->expects(self::atLeastOnce())
            ->method('escapeHtml')
            ->willReturnCallback(static fn (string $text): string => '[' . $text . ']');

        $html = (new ScimSetupInfo($this->urlBuilder, $escaper))->getHtml();

        self::assertStringContainsString('<li>[Endpoint URL: ' . self::ENDPOINT . ']</li>', $html);
        preg_match_all('/>([^<]*)</', $html, $matches);
        foreach (array_filter(array_map('trim', $matches[1])) as $text) {
            self::assertMatchesRegularExpression('/^\[[^\]]*\]( \[[^\]]*\])*$/', $text);
        }
    }

    public function testHtmlStatesTheAutoProvisioningPrecondition(): void
    {
        $html = $this->setupInfo->getHtml();

        self::assertStringContainsString('Google Admin console', $html);
        self::assertStringContainsString('Auto-provisioning section with Endpoint URL and Access token', $html);
        self::assertStringContainsString('catalog apps', $html);
        self::assertStringContainsString('admin-sso-google', $html);
    }

    public function testHtmlDocumentsTheAccessToken(): void
    {
        $html = $this->setupInfo->getHtml();

        self::assertStringContainsString('Access token', $html);
        self::assertStringContainsString('Bearer Token from Stores → Configuration → DMLab → Admin SCIM', $html);
    }

    public function testHtmlExplainsDefaultRoleInsteadOfGroupMapping(): void
    {
        $html = $this->setupInfo->getHtml();

        self::assertStringContainsString('users only, not groups', $html);
        self::assertStringContainsString('Group → Role Map does not apply', $html);
        self::assertStringContainsString('Default Role Id; set it', $html);
        self::assertStringContainsString('role assigned manually is reset on the next sync', $html);
    }

    public function testHtmlPointsTheProviderSelectorAtNone(): void
    {
        $html = $this->setupInfo->getHtml();

        self::assertStringContainsString('Provider: leave it at "— None —"', $html);
        self::assertDoesNotMatchRegularExpression('/set (it )?to "?google/i', $html);
    }

    public function testHtmlMentionsTheSyncInterval(): void
    {
        self::assertStringContainsString('every few hours', $this->setupInfo->getHtml());
    }

    public function testHtmlNamesConfigLabelsThatExistInTheCore(): void
    {
        $core = dirname((string)(new \ReflectionClass(EndpointUrlBuilder::class))->getFileName(), 3);
        $systemXml = (string)file_get_contents($core . '/etc/adminhtml/system.xml');
        $html = $this->setupInfo->getHtml();

        foreach (['DMLab', 'Admin SCIM', 'Enable Admin SCIM', 'Provider', 'Bearer Token', 'Group → Role Map',
            'Default Role Id'] as $label) {
            self::assertStringContainsString($label, $html);
            self::assertStringContainsString('<label>' . $label . '</label>', $systemXml);
        }
        self::assertStringContainsString(
            "'— None —'",
            (string)file_get_contents($core . '/Model/Config/Source/Provider.php')
        );
    }

    public function testHtmlMakesNoUndocumentedClaims(): void
    {
        $html = $this->setupInfo->getHtml();

        self::assertStringNotContainsString('group membership', $html);
        self::assertStringNotContainsString('active = false', $html);
        self::assertStringNotContainsString('RFC-7644 shape', $html);
        self::assertStringNotContainsString('no Google-side workaround', $html);
    }
}
