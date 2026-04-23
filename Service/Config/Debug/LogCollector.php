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
use Magento\Framework\Filesystem\Driver\File;

class LogCollector implements CollectorInterface
{
    public const MAX_LINES = 10000;

    private const KNOWN_FILES = ['mollie.log', 'system.log', 'exception.log', 'debug.log'];
    private const ROTATED_PATTERN = '/^mollie-.+\.log$/';
    private const MAX_ROTATED_FILES = 10;

    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly File $driver,
    ) {
    }

    /**
     * Returns the last MAX_LINES lines of each available Mollie-relevant log file.
     *
     * @return array<string, string> Map of filename => bounded contents
     */
    public function collect(): array
    {
        try {
            $logDir = $this->filesystem->getDirectoryRead(DirectoryList::LOG);
        } catch (\Throwable) {
            return [];
        }

        $candidates = [];
        foreach (self::KNOWN_FILES as $name) {
            $candidates[$name] = true;
        }

        $rotated = $this->discoverRotatedLogs($logDir);
        foreach ($rotated as $name) {
            $candidates[$name] = true;
        }

        $results = [];
        foreach (array_keys($candidates) as $name) {
            try {
                if (!$logDir->isExist($name) || !$logDir->isFile($name)) {
                    continue;
                }
                $absolute = $logDir->getAbsolutePath($name);
                $contents = $this->tail($absolute, self::MAX_LINES);
            } catch (FileSystemException) {
                continue;
            }

            if ($contents === null) {
                continue;
            }
            $results['logs/' . $name] = $contents;
        }

        return $results;
    }

    public function getReadmeDescription(): string
    {
        return "- logs/\n"
            . "  The last " . self::MAX_LINES . " lines of each relevant Magento log file (mollie.log,\n"
            . "  system.log, exception.log, debug.log, and any rotated mollie-*.log files).\n"
            . "  Smaller log files are included in full. Log entries may contain order\n"
            . "  numbers, customer email addresses, and similar transactional details that\n"
            . "  Magento itself writes during normal operation.";
    }

    /**
     * Returns rotated mollie-*.log filenames in the log directory, or [] on failure.
     *
     * @param \Magento\Framework\Filesystem\Directory\ReadInterface $logDir
     * @return string[]
     */
    private function discoverRotatedLogs(Filesystem\Directory\ReadInterface $logDir): array
    {
        try {
            if (!$logDir->isExist('') || !$logDir->isDirectory('')) {
                return [];
            }
            $found = [];
            foreach ($logDir->read('') as $entry) {
                if (preg_match(self::ROTATED_PATTERN, $entry)) {
                    $found[] = $entry;
                }
            }
            rsort($found);
            return array_slice($found, 0, self::MAX_ROTATED_FILES);
        } catch (FileSystemException) {
            return [];
        }
    }

    /**
     * Reads the file backwards in chunks until at least $maxLines newlines are buffered.
     *
     * @param string $path Absolute filesystem path to the log file
     * @param int $maxLines Maximum number of trailing lines to keep
     */
    private function tail(string $path, int $maxLines): ?string
    {
        try {
            $stat = $this->driver->stat($path);
        } catch (FileSystemException) {
            return null;
        }

        $size = (int) ($stat['size'] ?? 0);
        if ($size === 0) {
            return '';
        }

        try {
            $handle = $this->driver->fileOpen($path, 'rb');
        } catch (FileSystemException) {
            return null;
        }

        try {
            $position = $size;
            $chunkSize = 8192;
            $buffer = '';
            $newlineCount = 0;

            while ($position > 0 && $newlineCount <= $maxLines) {
                $read = (int) min($chunkSize, $position);
                $position -= $read;
                $this->driver->fileSeek($handle, $position);
                $data = $this->driver->fileRead($handle, $read);
                if ($data === '' || $data === false) {
                    break;
                }
                $buffer = $data . $buffer;
                $newlineCount += substr_count($data, "\n");
            }
        } catch (FileSystemException) {
            $this->driver->fileClose($handle);
            return null;
        }

        $this->driver->fileClose($handle);

        if ($newlineCount <= $maxLines) {
            return $buffer;
        }

        $parts = explode("\n", $buffer);
        $tail = array_slice($parts, -($maxLines + 1));
        return implode("\n", $tail);
    }
}
