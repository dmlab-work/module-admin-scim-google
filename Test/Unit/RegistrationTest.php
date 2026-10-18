<?php
/**
 * Copyright © DMLab. All rights reserved.
 * Licensed under the Open Software License version 3.0 (OSL-3.0). See LICENSE.
 */
declare(strict_types=1);

namespace DmLab\AdminScimGoogle\Test\Unit;

use Magento\Framework\Component\ComponentRegistrar;
use PHPUnit\Framework\TestCase;

class RegistrationTest extends TestCase
{
    public function testModuleIsRegistered(): void
    {
        $paths = (new ComponentRegistrar())->getPaths(ComponentRegistrar::MODULE);

        self::assertArrayHasKey('DmLab_AdminScimGoogle', $paths);
    }

    public function testRegisteredPathPointsAtThisModule(): void
    {
        $paths = (new ComponentRegistrar())->getPaths(ComponentRegistrar::MODULE);
        $path = $paths['DmLab_AdminScimGoogle'] ?? null;

        self::assertNotNull($path);
        self::assertDirectoryExists($path);
        self::assertFileExists($path . '/etc/module.xml');
    }

    public function testModuleSequencesAfterItsDependencies(): void
    {
        $sequenced = [];
        foreach ($this->loadModuleXml()->getElementsByTagName('sequence') as $sequence) {
            foreach ($sequence->getElementsByTagName('module') as $module) {
                $sequenced[] = $module->getAttribute('name');
            }
        }

        self::assertContains('DmLab_AdminScim', $sequenced);
        self::assertContains('Magento_Config', $sequenced);
        self::assertContains('Magento_Backend', $sequenced);
    }

    public function testSetupVersionMatchesReadmeBadge(): void
    {
        $module = $this->loadModuleXml()->getElementsByTagName('module')->item(0);
        self::assertNotNull($module);
        $setupVersion = $module->getAttribute('setup_version');

        $readme = (string)file_get_contents(dirname(__DIR__, 2) . '/README.md');
        self::assertMatchesRegularExpression('#badge/version-([0-9.]+)-#', $readme);
        preg_match('#badge/version-([0-9.]+)-#', $readme, $matches);

        self::assertSame($matches[1], $setupVersion);
    }

    private function loadModuleXml(): \DOMDocument
    {
        $paths = (new ComponentRegistrar())->getPaths(ComponentRegistrar::MODULE);
        $dom = new \DOMDocument();
        self::assertTrue($dom->load(($paths['DmLab_AdminScimGoogle'] ?? '') . '/etc/module.xml'));

        return $dom;
    }
}
