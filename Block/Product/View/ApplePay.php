<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Block\Product\View;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;

class ApplePay extends Template
{
    /**
     * @var Registry
     */
    private $registry;

    public function __construct(
        Template\Context $context,
        Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->registry = $registry;
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
        return $this->_scopeConfig->getValue('general/country/default');
    }

    public function getCurrencyCode(): string
    {
        return $this->_storeManager->getStore()->getCurrentCurrency()->getCode();
    }
}
