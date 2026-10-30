<?php
/**
 * Copyright © DMLab. All rights reserved.
 * Licensed under the Open Software License version 3.0 (OSL-3.0). See LICENSE.
 */
declare(strict_types=1);

namespace DmLab\AdminScimGoogle\Test\Unit;

use PHPUnit\Framework\TestCase;

class ReadmeTest extends TestCase
{
    private const IMAGE = '.github/images/config.png';

    /**
     * @var string
     */
    private string $readme;

    protected function setUp(): void
    {
        $this->readme = (string)file_get_contents($this->root() . '/README.md');
        self::assertNotSame('', $this->readme);
    }

    public function testHeaderIsTitleTaglineAndOneBadgeLine(): void
    {
        $lines = explode("\n", $this->readme);

        self::assertSame('# DmLab_AdminScimGoogle', $lines[0]);
        self::assertStringStartsWith('> ', $lines[2]);
        self::assertStringStartsWith('[![CI](', $lines[4]);
        self::assertStringEndsWith('![Magento](https://img.shields.io/badge/Magento-2.4-orange)', $lines[4]);
        self::assertSame(1, substr_count($this->readme, 'img.shields.io/badge/Magento'));
    }

    public function testSectionsAppearInBaselineOrder(): void
    {
        $markers = [
            '**Status: experimental.**',
            '![Google Workspace auto-provisioning setup in Magento admin: endpoint URL and access token]('
                . self::IMAGE . ')',
            "\n## Install\n",
            "\n## Set up Google Workspace auto-provisioning\n",
            "\n## Supported provisioning actions\n",
            "\n## Requirements\n",
            "\n## Architecture\n",
            "\n## License\n",
        ];

        $previous = strpos($this->readme, '[![CI](');
        self::assertNotFalse($previous);
        foreach ($markers as $marker) {
            $position = strpos($this->readme, $marker);
            self::assertNotFalse($position, 'Missing: ' . $marker);
            self::assertGreaterThan($previous, $position, 'Out of order: ' . $marker);
            $previous = $position;
        }
    }

    public function testExperimentalNoticePointsToAdminSsoGoogle(): void
    {
        self::assertMatchesRegularExpression(
            '/^> \*\*Status: experimental\.\*\*.*Auto-provisioning.*'
            . preg_quote('https://github.com/dmlab-work/module-admin-sso-google', '/') . '/m',
            $this->readme
        );
    }

    public function testRequirementsLine(): void
    {
        self::assertStringContainsString(
            "## Requirements\n\n"
            . "Magento 2.4.7+ · PHP 8.2–8.5 · `dmlab/module-admin-scim` ^0.2.0 (installed automatically)\n",
            $this->readme
        );
        self::assertStringNotContainsString('tested on', $this->readme);
    }

    public function testInstallCommands(): void
    {
        self::assertStringContainsString(
            "composer require dmlab/module-admin-scim-google\n"
            . "bin/magento module:enable DmLab_AdminScimGoogle\n"
            . "bin/magento setup:upgrade\n",
            $this->readme
        );
    }

    public function testReferencedImageExists(): void
    {
        self::assertFileExists($this->root() . '/' . self::IMAGE);
    }

    public function testNoRelativeModuleLinks(): void
    {
        self::assertStringNotContainsString('](../', $this->readme);
        self::assertStringNotContainsString('../module-', $this->readme);
        self::assertStringContainsString('](https://github.com/dmlab-work/module-admin-scim)', $this->readme);
    }

    private function root(): string
    {
        return dirname(__DIR__, 2);
    }
}
