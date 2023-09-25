<?php

namespace Mollie\Payment\Block\Applepay\Shortcut;

use Magento\Catalog\Block\ShortcutInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\ScopeInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Service\Mollie\ApplePay\SupportedNetworks;

class Button extends Template implements ShortcutInterface
{
    protected $_template = 'Mollie_Payment::applepay/minicart/applepay-button.phtml';

    /**
     * @var Session
     */
    private $checkoutSession;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var SupportedNetworks
     */
    private $supportedNetworks;

    public function __construct(
        Template\Context $context,
        Session $checkoutSession,
        Config $config,
        SupportedNetworks $supportedNetworks,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->checkoutSession = $checkoutSession;
        $this->config = $config;
        $this->supportedNetworks = $supportedNetworks;
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
        return $this->checkoutSession->getQuote()->getBaseGrandTotal();
    }

    /**
     * @throws \Magento\Framework\Exception\NoSuchEntityException
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
        return $this->supportedNetworks->execute((int)$this->_storeManager->getStore()->getId());
    }
}
