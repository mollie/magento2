<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\SelfTests;

use Magento\Framework\Module\ModuleListInterface;

class TestGeoIpModuleEnabled extends AbstractSelfTest
{
    public function __construct(
        private ModuleListInterface $moduleList
    ) {}

    public function execute(): void
    {
        $matches = preg_grep('/geo_?ip/i', $this->moduleList->getNames());

        if ($matches === []) {
            return;
        }

        $this->addMessage(
            'warning',
            __(
                'Detected enabled GeoIP module(s): %1. GeoIP modules can interfere with Mollie webhook processing by redirecting or rewriting requests based on the visitor\'s IP. Please exclude the Mollie webhook URL and return URL from any GeoIP redirect or store-switch logic.',
                implode(', ', $matches)
            )
        );
    }
}
