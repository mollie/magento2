<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Api\Data;

interface PaymentLinkRedirectResultInterface
{
    /**
     * @return bool
     */
    public function isAlreadyPaid(): bool;

    /**
     * @return string|null
     */
    public function getRedirectUrl(): ?string;

    /**
     * @return bool
     */
    public function isExpired(): bool;
}
