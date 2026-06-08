<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\PaymentFee\Types;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote\Address\Total;
use Mollie\Payment\Service\Config\PaymentFee;
use Mollie\Payment\Service\PaymentFee\Result;
use Mollie\Payment\Service\PaymentFee\ResultFactory;
use Mollie\Payment\Service\Tax\TaxCalculate;

class Percentage implements TypeInterface
{
    public function __construct(
        private ResultFactory $resultFactory,
        private PaymentFee $config,
        private TaxCalculate $taxCalculate
    ) {}

    public function calculate(CartInterface $cart, Total $total): Result
    {
        $storeId = storeId($cart->getStoreId());
        $percentage = $this->config->getPercentage($cart->getPayment()->getMethod(), $storeId);

        $subtotal = $total->getData('base_subtotal_incl_tax');
        $shipping = $total->getBaseShippingInclTax();
        $discount = $total->getBaseDiscountAmount();

        if (!$subtotal) {
            $subtotal = $total->getTotalAmount('subtotal');
        }

        if ($discount && $this->config->includeDiscountInSurcharge((int)$storeId)) {
            $subtotal = $subtotal - abs($discount);
        }

        $amount = $subtotal;
        if ($this->config->includeShippingInSurcharge($storeId)) {
            $amount = $subtotal + $shipping;
        }

        $calculatedResult = ($amount / 100) * $percentage;
        $tax = $this->taxCalculate->getTaxFromAmountIncludingTax($cart, $calculatedResult);

        /** @var Result $result */
        $result = $this->resultFactory->create();
        $result->setAmount($calculatedResult - $tax);
        $result->setTaxAmount($tax);

        return $result;
    }
}
