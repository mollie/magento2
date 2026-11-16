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
    public const XML_PATH_SHOW_PRODUCT_PAGE = 'payment/mollie_methods_expresscomponents/show_product_page';
    public const XML_PATH_SHOW_MINICART = 'payment/mollie_methods_expresscomponents/show_minicart';
    public const XML_PATH_SHOW_SHIPPING_STEP = 'payment/mollie_methods_expresscomponents/show_shipping_step';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
    ) {}

    public function isEnabled(?int $storeId = null): bool
    {
        return $this->isSetFlag(self::XML_PATH_ENABLED, $storeId);
    }

    public function shouldShowOnProductPage(?int $storeId = null): bool
    {
        return $this->isEnabled($storeId) && $this->isSetFlag(self::XML_PATH_SHOW_PRODUCT_PAGE, $storeId);
    }

    public function shouldShowInMinicart(?int $storeId = null): bool
    {
        return $this->isEnabled($storeId) && $this->isSetFlag(self::XML_PATH_SHOW_MINICART, $storeId);
    }

    public function shouldShowOnShippingStep(?int $storeId = null): bool
    {
        return $this->isEnabled($storeId) && $this->isSetFlag(self::XML_PATH_SHOW_SHIPPING_STEP, $storeId);
    }

    private function isSetFlag(string $path, ?int $storeId): bool
    {
        return $this->scopeConfig->isSetFlag($path, ScopeInterface::SCOPE_STORE, $storeId);
    }
}
