<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\PaymentFee;

use Magento\Quote\Api\Data\CartInterface;
use Mollie\Payment\Model\Adminhtml\Source\PaymentFeeType;
use Mollie\Payment\Service\Config\PaymentFee;
use Mollie\Payment\Service\Tax\TaxCalculate;

class MaximumSurcharge
{
    public function __construct(
        private PaymentFee $config,
        private TaxCalculate $taxCalculate
    ) {}

    public function calculate(CartInterface $cart, Result $result): void
    {
        $paymentFeeType = $this->config->getType($cart);
        if ($paymentFeeType == PaymentFeeType::FIXED_FEE) {
            return;
        }

        $maximumAmount = $this->config->getLimit($cart);
        if (!$maximumAmount || $maximumAmount > $result->getAmountIncludingTax()) {
            return;
        }

        $tax = $this->taxCalculate->getTaxFromAmountIncludingTax($cart, $maximumAmount);

        $result->setAmount($maximumAmount - $tax);
        $result->setTaxAmount($tax);
    }
}
