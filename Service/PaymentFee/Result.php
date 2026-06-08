<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

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
     * @return float
     */
    public function getRoundedAmount(): float
    {
        return round($this->amount, 2);
    }

    /**
     * @param float $amount
     */
    public function setAmount($amount): void
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
     * @return float
     */
    public function getRoundedTaxAmount(): float
    {
        return round($this->taxAmount, 2);
    }

    /**
     * @param float $taxAmount
     */
    public function setTaxAmount(float $taxAmount): void
    {
        $this->taxAmount = $taxAmount;
    }

    /**
     * @return float
     */
    public function getAmountIncludingTax(): float|int|array
    {
        return $this->amount + $this->taxAmount;
    }
}
