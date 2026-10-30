<?php
/**
 * Copyright © DMLab. All rights reserved.
 * Licensed under the Open Software License version 3.0 (OSL-3.0). See LICENSE.
 */
declare(strict_types=1);

namespace DmLab\AdminScimGoogle\Test\Unit;

use PHPUnit\Framework\TestCase;

class ComposerJsonTest extends TestCase
{
    private const REPO = 'https://github.com/dmlab-work/module-admin-scim-google';

    public function testRuntimeConstraintsCoverMagento247AndPhp82To85(): void
    {
        self::assertSame([
            'php' => '~8.2.0||~8.3.0||~8.4.0||~8.5.0',
            'magento/framework' => '^103.0.7',
            'magento/module-backend' => '^102.0.7',
            'magento/module-config' => '^101.2.7',
            'dmlab/module-admin-scim' => '^0.2.0',
        ], $this->composer()['require'] ?? null);
    }

    public function testPhpunitConstraintAllowsPhp82(): void
    {
        self::assertSame(['phpunit/phpunit' => '^11.5||^12.5'], $this->composer()['require-dev'] ?? null);
    }

    public function testKeywordsArePresent(): void
    {
        $keywords = $this->composer()['keywords'] ?? [];

        foreach ([
            'magento2',
            'magento2-module',
            'magento 2',
            'scim',
            'scim 2.0',
            'provisioning',
            'deprovisioning',
            'offboarding',
            'google workspace',
            'cloud identity',
            'auto-provisioning',
            'admin users',
        ] as $keyword) {
            self::assertContains($keyword, $keywords);
        }
    }

    public function testHomepageAndSupportLinks(): void
    {
        $composer = $this->composer();

        self::assertSame(self::REPO, $composer['homepage'] ?? null);
        self::assertSame(self::REPO . '/issues', $composer['support']['issues'] ?? null);
        self::assertSame(self::REPO, $composer['support']['source'] ?? null);
        self::assertArrayNotHasKey('suggest', $composer);
    }

    public function testChangelogTopSectionMatchesVersion(): void
    {
        $changelog = (string)file_get_contents(dirname(__DIR__, 2) . '/CHANGELOG.md');

        self::assertSame(1, preg_match('/^## (\S+)$/m', $changelog, $m));
        self::assertSame($this->composer()['version'] ?? null, $m[1]);
    }

    /**
     * @return array<string, mixed>
     */
    private function composer(): array
    {
        return json_decode(
            (string)file_get_contents(dirname(__DIR__, 2) . '/composer.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }
}
