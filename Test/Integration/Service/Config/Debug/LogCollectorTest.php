<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Config\Debug;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Mollie\Payment\Service\Config\Debug\LogCollector;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class LogCollectorTest extends IntegrationTestCase
{
    private const TEST_FILENAME = 'mollie-integration-test.log';
    private ?Filesystem\Directory\WriteInterface $logDir = null;

    protected function setUp(): void
    {
        parent::setUp();
        $filesystem = $this->objectManager->get(Filesystem::class);
        $this->logDir = $filesystem->getDirectoryWrite(DirectoryList::LOG);
    }

    protected function tearDown(): void
    {
        if ($this->logDir->isExist(self::TEST_FILENAME)) {
            $this->logDir->delete(self::TEST_FILENAME);
        }
        parent::tearDown();
    }

    private function writeLines(int $count): void
    {
        $lines = [];
        for ($i = 1; $i <= $count; $i++) {
            $lines[] = sprintf('[2024-01-01T00:00:00+00:00] test.INFO: Log line %d', $i);
        }
        $this->logDir->writeFile(self::TEST_FILENAME, implode("\n", $lines) . "\n");
    }

    private function collect(): array
    {
        return $this->objectManager->create(LogCollector::class)->collect();
    }

    public function testFileWithFewerThanMaxLinesIsReturnedInFull(): void
    {
        $this->writeLines(100);

        $result = $this->collect();

        $this->assertArrayHasKey('logs/' . self::TEST_FILENAME, $result);
        $content = $result['logs/' . self::TEST_FILENAME];
        $this->assertStringContainsString('Log line 1', $content);
        $this->assertStringContainsString('Log line 100', $content);
    }

    public function testFileExceedingMaxLinesReturnsOnlyLastLines(): void
    {
        $total = LogCollector::MAX_LINES + 500;
        $this->writeLines($total);

        $result = $this->collect();

        $this->assertArrayHasKey('logs/' . self::TEST_FILENAME, $result);
        $content = $result['logs/' . self::TEST_FILENAME];

        $this->assertStringNotContainsString('Log line 1 ', $content);
        $this->assertStringContainsString('Log line ' . $total, $content);
        $this->assertLessThanOrEqual(LogCollector::MAX_LINES + 1, substr_count($content, "\n"));
    }

    public function testEmptyFileReturnsEmptyString(): void
    {
        $this->logDir->writeFile(self::TEST_FILENAME, '');

        $result = $this->collect();

        $this->assertArrayHasKey('logs/' . self::TEST_FILENAME, $result);
        $this->assertSame('', $result['logs/' . self::TEST_FILENAME]);
    }

    public function testMissingFileIsNotIncluded(): void
    {
        $result = $this->collect();

        $this->assertArrayNotHasKey('logs/' . self::TEST_FILENAME, $result);
    }

    public function testResultKeysArePrefixedWithLogsDirectory(): void
    {
        $this->writeLines(10);

        $result = $this->collect();

        foreach (array_keys($result) as $key) {
            $this->assertStringStartsWith('logs/', $key);
        }
    }
}
