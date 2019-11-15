<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Api\Webapi;

interface PaymentTokenRequestInterface
{
    /**
     * @param string $cartId
     * @return string
     */
    public function generateForCustomer($cartId): string;

    /**
     * @param string $cartId
     * @return string
     */
    public function generateForGuest($cartId): string;
}
