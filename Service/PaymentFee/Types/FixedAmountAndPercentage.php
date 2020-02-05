<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\PaymentFee\Types;


use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote\Address\Total;
use Mollie\Payment\Service\PaymentFee\Result;
use Mollie\Payment\Service\PaymentFee\ResultFactory;

class FixedAmountAndPercentage implements TypeInterface
{
    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @var FixedAmount
     */
    private $fixedAmount;

    /**
     * @var Percentage
     */
    private $percentage;

    public function __construct(
        ResultFactory $resultFactory,
        FixedAmount $fixedAmount,
        Percentage $percentage
    ) {
        $this->fixedAmount = $fixedAmount;
        $this->percentage = $percentage;
        $this->resultFactory = $resultFactory;
    }

    /**
     * @inheritDoc
     */
    public function calculate(CartInterface $cart, Total $total)
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
