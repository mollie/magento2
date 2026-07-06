<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\Order\AdditionalData;

class MollieAmount
{
    private $value;
    private $currency;

    public function __construct(
        string $value,
        string $currency
    ) {
        $this->value = $value;
        $this->currency = $currency;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
