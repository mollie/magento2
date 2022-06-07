<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Mollie;

use Mollie\Payment\Config;

class DashboardUrl
{
    /**
     * @var Config
     */
    private $config;

    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    public function forOrdersApi($storeId, string $id): string
    {
        return str_replace(
            '{id}',
            $id,
            $this->config->getDashboardUrlForOrdersApi($storeId)
        );
    }

    public function forPaymentsApi($storeId, string $id): string
    {
        return str_replace(
            '{id}',
            $id,
            $this->config->getDashboardUrlForPaymentsApi($storeId)
        );
    }
}
