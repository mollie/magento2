<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Api\Webapi;

interface StartTransactionRequestInterface
{
    /**
     * @param string $cartId
     * @param int $orderId
     * @return string
     */
    public function execute($token);
}
