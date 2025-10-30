<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\PaymentFee\Types;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote\Address\Total;
use Mollie\Payment\Service\PaymentFee\Result;
use Mollie\Payment\Service\PaymentFee\ResultFactory;

class FixedAmountAndPercentage implements TypeInterface
{
    public function __construct(
        private ResultFactory $resultFactory,
        private FixedAmount $fixedAmount,
        private Percentage $percentage
    ) {}

    public function calculate(CartInterface $cart, Total $total): Result
    {
        $percentage = $this->percentage->calculate($cart, $total);
        $fixedAmount = $this->fixedAmount->calculate($cart, $total);

        /** @var Result $result */
        $result = $this->resultFactory->create();
        $result->setAmount($percentage->getAmount() + $fixedAmount->getAmount());
        $result->setTaxAmount($percentage->getTaxAmount() + $fixedAmount->getTaxAmount());

        return $result;
    }
}
