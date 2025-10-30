<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Block\Applepay\Shortcut;

use Magento\Catalog\Block\ShortcutInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Service\Mollie\ApplePay\SupportedNetworks;

class Button extends Template implements ShortcutInterface
{
    protected $_template = 'Mollie_Payment::applepay/minicart/applepay-button.phtml';

    public function __construct(
        Context $context,
        private Session $checkoutSession,
        private Config $config,
        private SupportedNetworks $supportedNetworks,
        array $data = [],
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return string
     */
    public function getAlias(): string
    {
        return 'mollie.applepay.mini-cart';
    }

    /**
     * @return float|null
     */
    public function getBaseGrandTotal(): ?float
    {
        return (float)$this->checkoutSession->getQuote()->getBaseGrandTotal();
    }

    /**
     * @throws NoSuchEntityException
     * @return string
     */
    public function getStoreName(): string
    {
        return $this->_storeManager->getStore()->getName();
    }

    /**
     * @return string
     */
    public function getStoreCountry(): string
    {
        return $this->_scopeConfig->getValue('general/country/default', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getStoreCurrency(): string
    {
        return $this->_storeManager->getStore()->getCurrentCurrencyCode();
    }

    public function getButtonClasses(): string
    {
        $classes = [];
        $classes[] = 'mollie-applepay-minicart-button';
        $classes[] = 'apple-pay-button';
        $classes[] = 'apple-pay-button-color-' . $this->config->applePayMinicartColor();

        if ($this->config->applePayMinicartText()) {
            $classes[] = 'apple-pay-button-text-' . $this->config->applePayMinicartText();
        }

        return implode(' ', $classes);
    }

    public function getSupportedNetworks(): array
    {
        return $this->supportedNetworks->execute((int) $this->_storeManager->getStore()->getId());
    }
}
