<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Block\Product\View;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Mollie\Payment\Config;
use Mollie\Payment\Service\Mollie\ApplePay\SupportedNetworks;

class ApplePay extends Template
{
    public function __construct(
        Context $context,
        private Registry $registry,
        private Config $config,
        private SupportedNetworks $supportedNetworks,
        array $data = [],
    ) {
        parent::__construct($context, $data);
    }

    public function getProductName(): string
    {
        $product = $this->registry->registry('product');

        if (!$product instanceof ProductInterface || !$product->getId()) {
            throw new LocalizedException(__('Failed to initialize product'));
        }

        return $product->getName();
    }

    public function getStoreName(): string
    {
        return $this->_storeManager->getStore()->getName();
    }

    public function getCountryCode(): string
    {
        return $this->_scopeConfig->getValue('general/country/default') ?? '';
    }

    public function getCurrencyCode(): string
    {
        return $this->_storeManager->getStore()->getCurrentCurrency()->getCode() ?? '';
    }

    public function isEnabled(): bool
    {
        return $this->config->isMethodActive('mollie_methods_applepay') &&
            $this->config->applePayEnableBuyNowButton();
    }

    public function getButtonClasses(): string
    {
        $classes = [];
        $classes[] = 'mollie-product-page-apple-pay-button';
        $classes[] = 'apple-pay-button';
        $classes[] = 'apple-pay-button-color-' . $this->config->applePayBuyNowColor();

        if ($this->config->applePayBuyNowText()) {
            $classes[] = 'apple-pay-button-text-' . $this->config->applePayBuyNowText();
        }

        return implode(' ', $classes);
    }

    public function getSupportedNetworks(): array
    {
        return $this->supportedNetworks->execute((int) $this->_storeManager->getStore()->getId());
    }
}
