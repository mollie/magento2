<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\Express;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Shipping options, address collection and the product page need features that Mollie only enables for accounts
 * in its beta. Mollie rejects the sessions of other accounts, so these stay off until the merchant is activated.
 */
class ExpressComponentsBeta
{
    public const XML_PATH_ENABLED = 'payment/mollie_methods_expresscomponents/beta_shipping_options';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
    ) {}

    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE, $storeId);
    }
}
