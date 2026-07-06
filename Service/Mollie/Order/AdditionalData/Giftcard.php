<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\Order\AdditionalData;

class Giftcard
{
    /**
     * @var string
     */
    private $issuer;
    /**
     * @var MollieAmount
     */
    private $amount;
    /**
     * @var string
     */
    private $voucherNumber;

    public function __construct(
        string $issuer,
        MollieAmount $amount,
        string $voucherNumber
    ) {
        $this->issuer = $issuer;
        $this->amount = $amount;
        $this->voucherNumber = $voucherNumber;
    }

    public function getAmount(): MollieAmount
    {
        return $this->amount;
    }

    public function getIssuer(): string
    {
        return $this->issuer;
    }

    public function getVoucherNumber(): string
    {
        return $this->voucherNumber;
    }
}
