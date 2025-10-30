<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie;

use Mollie\Payment\Config;

class DashboardUrl
{
    public function __construct(
        private Config $config
    ) {}

    public function forPaymentsApi(?int $storeId, string $id): string
    {
        return str_replace(
            '{id}',
            $id,
            $this->config->getDashboardUrlForPaymentsApi($storeId),
        );
    }
}
