<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Tracking;

use Magento\Framework\Stdlib\CookieManagerInterface;
use Mollie\Payment\Config\TrackingCookies;

class CookieCollector
{
    public function __construct(
        private readonly CookieManagerInterface $cookieManager,
        private readonly TrackingCookies $trackingCookiesConfig,
    ) {}

    /**
     * @return array<string, string> alias → raw cookie value
     */
    public function collect(?int $storeId = null): array
    {
        $collected = [];
        foreach ($this->trackingCookiesConfig->get($storeId) as $config) {
            $value = $this->cookieManager->getCookie($config->cookieName);
            if ($value === null || $value === '') {
                continue;
            }

            $collected[$config->alias] = (string) $value;
        }

        return $collected;
    }
}
