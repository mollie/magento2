<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Config\Debug;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Framework\Filesystem\Driver\File;

class ComposerCollector implements CollectorInterface
{
    public const FILES = ['composer.json', 'composer.lock'];
    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10 MB
    private const BACKUP_PATTERNS = ['composer*bak*', 'composer*.orig'];

    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly File $driver,
    ) {
    }

    public function getReadmeDescription(): string
    {
        return "- composer/\n"
            . "  Verbatim copies of composer.json, composer.lock, and any backup files\n"
            . "  (e.g. composer.lock-bak*) found in the Magento root.\n"
            . "  These reveal the exact versions of every installed package, which lets\n"
            . "  support reproduce your environment.";
    }

    /**
     * @return array<string, string> Map of archive-relative path => file contents
     */
    public function collect(): array
    {
        try {
            $rootDir = $this->filesystem->getDirectoryRead(DirectoryList::ROOT);
        } catch (\Throwable) {
            return [];
        }

        $results = [];

        foreach (array_merge(self::FILES, $this->findBackupFiles($rootDir)) as $name) {
            $results = array_merge($results, $this->readFile($rootDir, $name));
        }

        return $results;
    }

    private function findBackupFiles(ReadInterface $rootDir): array
    {
        $found = [];
        foreach (self::BACKUP_PATTERNS as $pattern) {
            try {
                $found = array_merge($found, $rootDir->search($pattern));
            } catch (\Throwable) {
                continue;
            }
        }

        return array_values(array_filter(
            array_map('basename', $found),
            fn(string $name) => $rootDir->isFile($name) && !in_array($name, self::FILES, true),
        ));
    }

    private function readFile(ReadInterface $rootDir, string $name): array
    {
        try {
            if (!$rootDir->isExist($name) || !$rootDir->isFile($name)) {
                return [];
            }
            $absolute = $rootDir->getAbsolutePath($name);
            $stat = $this->driver->stat($absolute);
            if (($stat['size'] ?? 0) > self::MAX_FILE_SIZE) {
                return ['composer/' . $name => sprintf(
                    'File too large (%d MB), skipped.',
                    (int) round(($stat['size'] ?? 0) / 1024 / 1024)
                )];
            }
            return ['composer/' . $name => $this->driver->fileGetContents($absolute)];
        } catch (FileSystemException) {
            return [];
        }
    }
}
