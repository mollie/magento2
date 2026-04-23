<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Config\Debug;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Mollie\Payment\Service\Config\Debug\DebugBundleGenerator;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

/**
 * @magentoAppArea adminhtml
 */
class DebugBundleGeneratorTest extends IntegrationTestCase
{
    private ?Filesystem\Directory\WriteInterface $varDirectory = null;
    private ?string $generatedPath = null;

    protected function setUp(): void
    {
        parent::setUp();
        $filesystem = $this->objectManager->get(Filesystem::class);
        $this->varDirectory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
    }

    protected function tearDown(): void
    {
        if ($this->generatedPath && $this->varDirectory->isExist($this->generatedPath)) {
            $this->varDirectory->delete($this->generatedPath);
        }
        parent::tearDown();
    }

    private function generate(): string
    {
        /** @var DebugBundleGenerator $generator */
        $generator = $this->objectManager->create(DebugBundleGenerator::class);
        $this->generatedPath = $generator->generate();
        return $this->generatedPath;
    }

    public function testGenerateReturnsTgzPath(): void
    {
        $path = $this->generate();

        $this->assertStringEndsWith('.tgz', $path);
        $this->assertTrue($this->varDirectory->isExist($path));
    }

    public function testArchiveContainsExpectedFiles(): void
    {
        $absolutePath = $this->varDirectory->getAbsolutePath($this->generate());

        $files = $this->listArchiveFiles($absolutePath);

        $this->assertContains('manifest.txt', $files);
        $this->assertContains('README.txt', $files);
        $this->assertContains('environment.txt', $files);
        $this->assertContains('modules.txt', $files);
        $this->assertContains('cache-status.txt', $files);
        $this->assertContains('indexer-status.txt', $files);
        $this->assertContains('mollie-config.json', $files);
        $this->assertContains('env.redacted.php.txt', $files);
        $this->assertContains('cron_schedule.csv', $files);
        $this->assertContains('database.txt', $files);
    }

    public function testManifestListsCollectedFiles(): void
    {
        $absolutePath = $this->varDirectory->getAbsolutePath($this->generate());

        $manifest = $this->readFileFromArchive($absolutePath, 'manifest.txt');

        $this->assertStringContainsString('environment.txt', $manifest);
        $this->assertStringContainsString('modules.txt', $manifest);
        $this->assertStringContainsString('Generated at:', $manifest);
    }

    public function testEnvironmentFileContainsMagentoVersion(): void
    {
        $absolutePath = $this->varDirectory->getAbsolutePath($this->generate());

        $environment = $this->readFileFromArchive($absolutePath, 'environment.txt');

        $this->assertStringContainsString('Magento version:', $environment);
        $this->assertStringContainsString('PHP version:', $environment);
        $this->assertStringContainsString('Deploy mode:', $environment);
    }

    public function testEnvFileContainsRedactedMarker(): void
    {
        $absolutePath = $this->varDirectory->getAbsolutePath($this->generate());

        $env = $this->readFileFromArchive($absolutePath, 'env.redacted.php.txt');

        // The integration-test env.php contains a db password, so the redacted marker must appear.
        $this->assertStringContainsString(\Mollie\Payment\Service\Config\Debug\EnvCollector::REDACTED, $env);
    }

    public function testBuildFilenameReturnsTgzExtension(): void
    {
        $generator = $this->objectManager->create(DebugBundleGenerator::class);

        $this->assertMatchesRegularExpression('/^mollie-debug-\d{8}-\d{6}\.tgz$/', $generator->buildFilename());
    }

    public function testNoStagingDirectoryLeftBehind(): void
    {
        $this->generate();

        $tmpContents = $this->varDirectory->read('tmp');
        $stagingDirs = array_filter($tmpContents, fn($f) => is_dir($this->varDirectory->getAbsolutePath($f)));

        $this->assertEmpty($stagingDirs, 'Staging directory was not cleaned up after generation');
    }

    private function listArchiveFiles(string $absolutePath): array
    {
        /** @var \Magento\Framework\Shell $shell */
        $shell = $this->objectManager->get(\Magento\Framework\Shell::class);
        $output = $shell->execute('tar -tzf %s', [$absolutePath]);
        return array_filter(array_map(fn($f) => ltrim($f, './'), explode(PHP_EOL, $output)));
    }

    private function readFileFromArchive(string $absolutePath, string $filename): string
    {
        /** @var \Magento\Framework\Shell $shell */
        $shell = $this->objectManager->get(\Magento\Framework\Shell::class);
        return $shell->execute('tar -xOzf %s %s', [$absolutePath, $filename]);
    }
}
