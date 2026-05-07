<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Config\Debug;

use Magento\Framework\App\ResourceConnection;

class PaymentMethodsCollector implements CollectorInterface
{
    public function __construct(
        private readonly ResourceConnection $resource,
    ) {
    }

    public function collect(): array
    {
        return ['payment-methods.txt' => $this->render()];
    }

    public function getReadmeDescription(): string
    {
        return "- payment-methods.txt\n"
            . "  Which Mollie payment methods are enabled or disabled per scope,\n"
            . "  as stored in core_config_data. No API keys or credentials.";
    }

    private function render(): string
    {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('core_config_data');

        $select = $connection->select()
            ->from($table, ['scope', 'scope_id', 'path', 'value'])
            ->where('path LIKE ?', 'payment/mollie_%/active')
            ->order(['scope ASC', 'scope_id ASC', 'path ASC']);

        $rows = $connection->fetchAll($select);

        if ($rows === []) {
            return "No Mollie payment method configuration found (all scopes using defaults).\n";
        }

        $grouped = [];
        foreach ($rows as $row) {
            $scope = $row['scope'] . '/' . $row['scope_id'];
            $method = $this->extractMethodCode($row['path']);
            $grouped[$scope][$method] = (int)$row['value'] === 1 ? 'enabled' : 'disabled';
        }

        $lines = [];
        foreach ($grouped as $scope => $methods) {
            $lines[] = $scope . ':';
            foreach ($methods as $method => $state) {
                $lines[] = '  ' . $method . ': ' . $state;
            }
        }

        return implode("\n", $lines) . "\n";
    }

    private function extractMethodCode(string $path): string
    {
        // payment/mollie_methods_ideal/active → mollie_methods_ideal
        $parts = explode('/', $path);
        return $parts[1] ?? $path;
    }
}
