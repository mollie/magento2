<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Config\Debug;

use Magento\Framework\App\DeploymentConfig\Reader;
use Magento\Framework\Config\File\ConfigFilePool;

class EnvCollector implements CollectorInterface
{
    public const REDACTED = '***REDACTED***';

    private const SENSITIVE_NEEDLES = ['password', 'secret', 'token', 'api_key', 'key', 'salt'];

    public function __construct(
        private readonly Reader $reader,
    ) {
    }

    /**
     * Returns a var_export-formatted, redacted view of app/etc/env.php.
     *
     * Structure is preserved; secrets are replaced with a sentinel string. Output is readable PHP
     * array syntax without the `<?php`/`return` wrapper so the file cannot be required.
     */
    public function collect(): array
    {
        return ['env.redacted.php.txt' => $this->render()];
    }

    public function getReadmeDescription(): string
    {
        return "- env.redacted.php.txt\n"
            . "  A copy of your app/etc/env.php with all secrets (encryption key, database\n"
            . "  password, cache/queue/session passwords, tokens, API keys) replaced by\n"
            . "  \"" . self::REDACTED . "\". The file extension is .txt so it cannot be\n"
            . "  executed or required back by mistake.";
    }

    public function render(): string
    {
        try {
            $config = $this->reader->load(ConfigFilePool::APP_ENV);
        } catch (\Throwable $e) {
            return "Unable to read app/etc/env.php: " . $e->getMessage() . "\n";
        }

        if ($config === []) {
            return "app/etc/env.php is empty or missing.\n";
        }

        $redacted = $this->redact($config, []);

        return var_export($redacted, true) . "\n";
    }

    /**
     * Recursively walks the array and redacts sensitive values.
     *
     * @param mixed $value Current value
     * @param string[] $path Path of string keys leading to $value
     * @return mixed
     */
    private function redact(mixed $value, array $path): mixed
    {
        if (is_array($value)) {
            $out = [];
            foreach ($value as $key => $child) {
                $childPath = $path;
                if (is_string($key)) {
                    $childPath[] = $key;
                }
                $out[$key] = $this->redact($child, $childPath);
            }
            return $out;
        }

        if ($path === []) {
            return $value;
        }

        $leaf = end($path);
        if (!is_string($leaf)) {
            return $value;
        }

        $lowerLeaf = strtolower($leaf);
        foreach (self::SENSITIVE_NEEDLES as $needle) {
            if (str_contains($lowerLeaf, $needle)) {
                return self::REDACTED;
            }
        }

        if ($this->isDbConnectionUsername($path)) {
            return self::REDACTED;
        }

        return $value;
    }

    /**
     * Matches paths shaped as db.connection.<connection>.username.
     *
     * @param string[] $path
     */
    private function isDbConnectionUsername(array $path): bool
    {
        return count($path) === 4
            && $path[0] === 'db'
            && $path[1] === 'connection'
            && $path[3] === 'username';
    }
}
