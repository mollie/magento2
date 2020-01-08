<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\PaymentFee;

use Magento\Quote\Api\Data\CartInterface;
use Mollie\Payment\Model\Adminhtml\Source\PaymentFeeType;
use Mollie\Payment\Service\Config\PaymentFee;
use Mollie\Payment\Service\Tax\TaxCalculate;

class MaximumSurcharge
{
    /**
     * @var PaymentFee
     */
    private $config;
    /**
     * @var TaxCalculate
     */
    private $taxCalculate;

    public function __construct(
        PaymentFee $config,
        TaxCalculate $taxCalculate
    ) {
        $this->config = $config;
        $this->taxCalculate = $taxCalculate;
    }

    public function calculate(CartInterface $cart, Result $result)
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
