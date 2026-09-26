<?php
/**
 * Copyright © DMLab. All rights reserved.
 */
declare(strict_types=1);

namespace DmLab\AdminScimGoogle\Test\Unit\Model\Google;

use Magento\Framework\Escaper;
use DmLab\AdminScim\Model\Discovery\EndpointUrlBuilder;
use DmLab\AdminScimGoogle\Model\Google\ScimSetupInfo;
use PHPUnit\Framework\TestCase;

/**
 * The setup surface must render the actual SCIM endpoint (from the core's URL
 * builder, HTML-escaped) as Google's Endpoint URL, plus the Google
 * auto-provisioning guidance an admin needs.
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

    public function testHtmlEscapesTheEndpointThroughTheEscaper(): void
    {
        $escaper = $this->createStub(Escaper::class);
        $escaper->method('escapeHtml')->willReturn('ESCAPED_ENDPOINT');

        $html = (new ScimSetupInfo($this->urlBuilder, $escaper))->getHtml();

        self::assertStringContainsString('ESCAPED_ENDPOINT', $html);
    }

    public function testHtmlDocumentsAccessTokenAndProvisioningGuidance(): void
    {
        $html = $this->setupInfo->getHtml();

        self::assertStringContainsString('Access token', $html);
        self::assertStringContainsString('bearer token', $html);
        self::assertStringContainsString('active = false', $html);
    }

    public function testHtmlPointsAtTheGoogleAdminConsoleAutoProvisioning(): void
    {
        $html = $this->setupInfo->getHtml();

        self::assertStringContainsString('Google Admin console', $html);
        self::assertStringContainsString('Auto-provisioning', $html);
    }
}
