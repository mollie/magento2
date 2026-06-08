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

class FixedAmount implements TypeInterface
{
    public function __construct(
        private ResultFactory $resultFactory,
        private PaymentFee $config,
        private TaxCalculate $taxCalculate
    ) {}

    public function calculate(CartInterface $cart, Total $total): Result
    {
        $amount = $this->config->getFixedAmount($cart->getPayment()->getMethod(), storeId($cart->getStoreId()));
        $tax = $this->taxCalculate->getTaxFromAmountIncludingTax($cart, $amount);

        /** @var Result $result */
        $result = $this->resultFactory->create();
        $result->setAmount($amount - $tax);
        $result->setTaxAmount($tax);

        return $result;
    }
}
