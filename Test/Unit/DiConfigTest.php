<?php
/**
 * Copyright © DMLab. All rights reserved.
 * Licensed under the Open Software License version 3.0 (OSL-3.0). See LICENSE.
 */
declare(strict_types=1);

namespace DmLab\AdminScimGoogle\Test\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Guards that the plugin registers no request normalizer: the core already coerces
 * Google's payloads, and a registered item would also run on other IdPs' requests.
 */
class DiConfigTest extends TestCase
{
    public function testNoDiConfigReferencesTheNormalizerChain(): void
    {
        $root = dirname(__DIR__, 2);
        $files = array_merge(glob($root . '/etc/di.xml') ?: [], glob($root . '/etc/*/di.xml') ?: []);

        self::assertStringNotContainsString(
            'RequestNormalizerChain',
            implode('', array_map('file_get_contents', $files)),
            'No etc/**/di.xml may touch the normalizer chain.'
        );
    }
}
