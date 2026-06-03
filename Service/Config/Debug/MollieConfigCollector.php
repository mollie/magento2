<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Config\Debug;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;

class MollieConfigCollector implements CollectorInterface
{
    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly ScopeConfigInterface $scopeConfig,
    ) {
    }

    /**
     * Renders the mollie-config.json body.
     */
    public function collect(): array
    {
        return ['mollie-config.json' => $this->render()];
    }

    public function getReadmeDescription(): string
    {
        return "- mollie-config.json\n"
            . "  The Mollie-owned configuration rows from your store's core_config_data\n"
            . "  table (all settings under \"payment/mollie*\"), plus a list of configuration\n"
            . "  paths that are using the built-in defaults. API key values are redacted.";
    }

    public function render(): string
    {
        $explicit = $this->fetchExplicitRows();
        $defaultedPaths = $this->collectDefaultedPaths($explicit);

        $payload = [
            'explicit_values' => $explicit,
            'defaulted_paths' => $defaultedPaths,
        ];

        return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    }

    /**
     * Fetches every core_config_data row whose path is Mollie-owned.
     *
     * API key values are replaced with [REDACTED].
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchExplicitRows(): array
    {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('core_config_data');

        $select = $connection->select()
            ->from($table, ['scope', 'scope_id', 'path', 'value'])
            ->where('path LIKE ? OR path = ?', 'payment/mollie%', 'sales/totals_sort/mollie_payment_fee')
            ->order(['path ASC', 'scope ASC', 'scope_id ASC']);

        $rows = $connection->fetchAll($select);

        $result = [];
        foreach ($rows as $row) {
            $path = (string)$row['path'];
            $result[] = [
                'scope' => (string)$row['scope'],
                'scope_id' => (int)$row['scope_id'],
                'path' => $path,
                'value' => str_contains($path, 'apikey') ? '[REDACTED]' : $row['value'],
            ];
        }

        return $result;
    }

    /**
     * Lists Mollie-owned config paths that have a default but no explicit override.
     *
     * @param array $explicit Explicit rows collected from core_config_data
     * @return string[]
     */
    private function collectDefaultedPaths(array $explicit): array
    {
        $knownPaths = $this->collectKnownMolliePaths();

        $explicitPaths = [];
        foreach ($explicit as $row) {
            $explicitPaths[$row['path']] = true;
        }

        $defaulted = [];
        foreach ($knownPaths as $path) {
            if (!isset($explicitPaths[$path])) {
                $defaulted[] = $path;
            }
        }

        sort($defaulted, SORT_STRING);
        return $defaulted;
    }

    /**
     * Enumerates every Mollie-owned config path from the default scope config tree.
     *
     * @return string[]
     */
    private function collectKnownMolliePaths(): array
    {
        $paths = [];

        $payment = $this->scopeConfig->getValue('payment');
        if (is_array($payment)) {
            foreach ($payment as $group => $values) {
                if (!is_string($group) || strpos($group, 'mollie') !== 0 || !is_array($values)) {
                    continue;
                }
                foreach ($this->flatten($values, 'payment/' . $group) as $path) {
                    $paths[] = $path;
                }
            }
        }

        $sales = $this->scopeConfig->getValue('sales/totals_sort');
        if (is_array($sales)) {
            foreach ($sales as $key => $value) {
                if (!is_string($key) || strpos($key, 'mollie') === false) {
                    continue;
                }
                $paths[] = 'sales/totals_sort/' . $key;
            }
        }

        $paths = array_values(array_unique($paths));
        sort($paths, SORT_STRING);
        return $paths;
    }

    /**
     * Flattens a nested config array into dot-less slash-joined leaf paths.
     *
     * @param array $values Nested config tree under $prefix
     * @param string $prefix Path prefix accumulated so far
     * @return string[]
     */
    private function flatten(array $values, string $prefix): array
    {
        $flat = [];
        foreach ($values as $key => $value) {
            if (!is_string($key)) {
                continue;
            }
            $path = $prefix . '/' . $key;
            if (is_array($value)) {
                foreach ($this->flatten($value, $path) as $nested) {
                    $flat[] = $nested;
                }
            } else {
                $flat[] = $path;
            }
        }
        return $flat;
    }
}
