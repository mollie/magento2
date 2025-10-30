<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Config;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Tax\Model\Calculation;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Adminhtml\Source\PaymentFeeType;

class PaymentFee
{
    public function __construct(
        private Config $config,
        private Calculation $taxCalculation
    ) {}

    /**
     * @param CartInterface $quote
     * @return bool
     */
    public function isAvailableForMethod(CartInterface $quote)
    {
        $method = $quote->getPayment()->getMethod();

        if ($this->config->paymentSurchargeType($method, storeId($quote->getStoreId())) == PaymentFeeType::DISABLED) {
            return false;
        }

        return substr($method, 0, 6) == 'mollie';
    }

    /**
     * @param CartInterface $quote
     * @return string|null
     */
    public function getType(CartInterface $quote)
    {
        $method = $quote->getPayment()->getMethod();

        return $this->config->paymentSurchargeType($method, storeId($quote->getStoreId()));
    }

    /**
     * @param $method
     * @param $storeId
     * @return float
     */
    public function getFixedAmount($method, ?int $storeId)
    {
        return $this->config->paymentSurchargeFixedAmount($method, $storeId);
    }

    /**
     * @param $method
     * @param $storeId
     * @return float
     */
    public function getPercentage($method, ?int $storeId): float
    {
        return (float) $this->config->paymentSurchargePercentage($method, $storeId);
    }

    /**
     * @param CartInterface $cart
     * @return string
     */
    public function getLimit(CartInterface $cart)
    {
        $method = $cart->getPayment()->getMethod();

        return $this->config->paymentSurchargeLimit($method, storeId($cart->getStoreId()));
    }

    /**
     * @param CartInterface $cart
     * @return string|null
     */
    public function getTaxClass(CartInterface $cart)
    {
        $method = $cart->getPayment()->getMethod();

        return $this->config->paymentSurchargeTaxClass($method, storeId($cart->getStoreId()));
    }

    /**
     * @param null $storeId
     * @return bool
     */
    public function includeShippingInSurcharge(?int $storeId = null): bool
    {
        return $this->config->includeShippingInSurcharge($storeId);
    }

    /**
     * @param null $storeId
     * @return bool
     */
    public function includeDiscountInSurcharge(?int $storeId = null): bool
    {
        return $this->config->includeDiscountInSurcharge($storeId);
    }
}
