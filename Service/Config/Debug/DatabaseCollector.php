<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Config\Debug;

use Magento\Framework\App\ResourceConnection;

class DatabaseCollector implements CollectorInterface
{
    public function __construct(
        private readonly ResourceConnection $resource,
    ) {
    }

    public function collect(): array
    {
        return ['database.txt' => $this->render()];
    }

    public function getReadmeDescription(): string
    {
        return "- database.txt\n"
            . "  The MySQL/MariaDB server version. Useful for diagnosing compatibility\n"
            . "  issues with JSON columns, locking behaviour, and query syntax.";
    }

    private function render(): string
    {
        try {
            $version = $this->resource->getConnection()->fetchOne('SELECT VERSION()');
        } catch (\Throwable $e) {
            return 'Unable to retrieve database version: ' . $e->getMessage() . "\n";
        }

        return 'Database version: ' . $version . "\n";
    }
}
