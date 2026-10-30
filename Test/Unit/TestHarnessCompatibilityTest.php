<?php
/**
 * Copyright © DMLab. All rights reserved.
 * Licensed under the Open Software License version 3.0 (OSL-3.0). See LICENSE.
 */
declare(strict_types=1);

namespace DmLab\AdminScimGoogle\Test\Unit;

use PHPUnit\Framework\TestCase;

class TestHarnessCompatibilityTest extends TestCase
{
    private const PROXY_GENERATOR = '\\Magento\\Framework\\TestFramework\\Unit\\Autoloader\\ProxyGenerator';

    public function testProxyGeneratorIsRegisteredOnlyWhenAvailable(): void
    {
        $bootstrap = (string)file_get_contents(__DIR__ . '/bootstrap.php');
        $proxy = preg_quote(self::PROXY_GENERATOR, '/');

        self::assertMatchesRegularExpression(
            '/if \\(class_exists\\(' . $proxy . '::class\\)\\) \\{\\s*\\$generators\\[\\] = new ' . $proxy . '\\(\\);/',
            $bootstrap
        );
        self::assertSame(1, substr_count($bootstrap, 'new ' . self::PROXY_GENERATOR . '('));
    }

    public function testPhpunitConfigUsesPhpunit115Schema(): void
    {
        $xml = simplexml_load_file(__DIR__ . '/phpunit.xml');
        self::assertNotFalse($xml);

        $location = (string)$xml->attributes('http://www.w3.org/2001/XMLSchema-instance')['noNamespaceSchemaLocation'];
        self::assertSame('https://schema.phpunit.de/11.5/phpunit.xsd', $location);
    }
}
