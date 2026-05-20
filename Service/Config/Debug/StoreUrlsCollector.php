<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Config\Debug;

use Magento\Framework\App\ResourceConnection;

class StoreUrlsCollector implements CollectorInterface
{
    private const PATHS = [
        'web/secure/base_url',
        'web/unsecure/base_url',
    ];

    public function __construct(
        private readonly ResourceConnection $resource,
    ) {
    }

    public function collect(): array
    {
        return ['store-urls.txt' => $this->render()];
    }

    public function getReadmeDescription(): string
    {
        return "- store-urls.txt\n"
            . "  The secure and unsecure base URLs for every scope. Useful for spotting\n"
            . "  mixed HTTP/HTTPS setups and misconfigured webhook return URLs.";
    }

    private function render(): string
    {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('core_config_data');

        $select = $connection->select()
            ->from($table, ['scope', 'scope_id', 'path', 'value'])
            ->where('path IN (?)', self::PATHS)
            ->order(['scope ASC', 'scope_id ASC', 'path ASC']);

        $rows = $connection->fetchAll($select);

        if ($rows === []) {
            return "No base URL configuration found (all scopes using defaults).\n";
        }

        $lines = [];
        foreach ($rows as $row) {
            $lines[] = sprintf(
                '%s/%s %s: %s',
                $row['scope'],
                $row['scope_id'],
                $row['path'],
                $row['value']
            );
        }

        return implode("\n", $lines) . "\n";
    }
}
