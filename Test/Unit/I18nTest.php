<?php
/**
 * Copyright © DMLab. All rights reserved.
 * Licensed under the Open Software License version 3.0 (OSL-3.0). See LICENSE.
 */
declare(strict_types=1);

namespace DmLab\AdminScimGoogle\Test\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Every translatable phrase in the module has a row in i18n/en_US.csv.
 */
class I18nTest extends TestCase
{
    public function testCodePhrasesAreInDictionary(): void
    {
        $root = $this->moduleRoot();
        $phrases = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            $relative = substr($file->getPathname(), strlen($root) + 1);
            if ($file->getExtension() !== 'php' || preg_match('#^(Test|vendor|docs)/#', $relative)) {
                continue;
            }
            preg_match_all(
                '/__\(\s*([\'"])((?:(?!\1)[^\\\\]|\\\\.)*)\1/',
                (string)file_get_contents($file->getPathname()),
                $m
            );
            foreach ($m[2] as $literal) {
                $phrases[] = stripslashes($literal);
            }
        }
        self::assertNotEmpty($phrases);

        $this->assertInDictionary($phrases);
    }

    public function testSystemXmlTranslatableStringsAreInDictionary(): void
    {
        $xml = simplexml_load_file($this->moduleRoot() . '/etc/adminhtml/system.xml');
        self::assertNotFalse($xml);

        $phrases = [];
        foreach ($xml->xpath('//*[@translate]') as $node) {
            foreach (preg_split('/\s+/', trim((string)$node['translate'])) as $child) {
                self::assertTrue(isset($node->{$child}), 'translate="' . $child . '" has no <' . $child . '>');
                $phrases[] = trim((string)$node->{$child});
            }
        }
        self::assertNotEmpty($phrases);

        $this->assertInDictionary($phrases);
    }

    /**
     * @param string[] $phrases
     */
    private function assertInDictionary(array $phrases): void
    {
        $dictionary = $this->dictionary();
        foreach ($phrases as $phrase) {
            self::assertArrayHasKey($phrase, $dictionary, 'Missing i18n row: ' . $phrase);
        }
    }

    /**
     * @return array<string, string>
     */
    private function dictionary(): array
    {
        $rows = [];
        $handle = fopen($this->moduleRoot() . '/i18n/en_US.csv', 'r');
        self::assertNotFalse($handle);
        while (($row = fgetcsv($handle, null, ',', '"', '')) !== false) {
            if (count($row) >= 2) {
                $rows[$row[0]] = $row[1];
            }
        }
        fclose($handle);

        return $rows;
    }

    private function moduleRoot(): string
    {
        return dirname(__DIR__, 2);
    }
}
