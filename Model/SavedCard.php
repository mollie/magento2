<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model;

use Mollie\Payment\Api\Data\SavedCardInterface;

class SavedCard implements SavedCardInterface
{
    public function __construct(
        private string $mandateId,
        private string $cardLabel,
        private string $cardNumberLast4,
        private ?string $cardExpiryDate,
        private ?string $cardHolder,
    ) {}

    public function getMandateId(): string
    {
        return $this->mandateId;
    }

    public function getCardLabel(): string
    {
        return $this->cardLabel;
    }

    public function getCardNumberLast4(): string
    {
        return $this->cardNumberLast4;
    }

    public function getCardExpiryDate(): ?string
    {
        return $this->cardExpiryDate;
    }

    public function getCardHolder(): ?string
    {
        return $this->cardHolder;
    }
}
