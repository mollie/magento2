<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\PaymentFee;


class Result
{
    /**
     * @var float
     */
    private $amount = 0;

    /**
     * @var float
     */
    private $taxAmount = 0;

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param float $amount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    /**
     * @return float
     */
    public function getTaxAmount()
    {
        return $this->taxAmount;
    }

    /**
     * @param float $taxAmount
     */
    public function setTaxAmount(float $taxAmount)
    {
        $this->taxAmount = $taxAmount;
    }

    /**
     * @return float
     */
    public function getAmountIncludingTax()
    {
        return $this->getAmount() + $this->getTaxAmount();
    }
}
