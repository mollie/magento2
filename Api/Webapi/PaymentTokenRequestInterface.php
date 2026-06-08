<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Api\Webapi;

interface PaymentTokenRequestInterface
{
    /**
     * @param string $cartId
     * @return string
     */
    public function generateForCustomer(string $cartId): string;

    /**
     * @param string $cartId
     * @return string
     */
    public function generateForGuest(string $cartId): string;
}
