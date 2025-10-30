<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Magento;

use Mollie\Payment\Api\Data\PaymentLinkRedirectResultInterface;

class PaymentLinkRedirectResult implements PaymentLinkRedirectResultInterface
{
    public function __construct(
        private bool $alreadyPaid,
        private bool $isExpired,
        private ?string $redirectUrl = null
    ) {}

    public function isAlreadyPaid(): bool
    {
        return $this->alreadyPaid;
    }

    public function getRedirectUrl(): ?string
    {
        return $this->redirectUrl;
    }

    public function isExpired(): bool
    {
        return $this->isExpired;
    }
}
