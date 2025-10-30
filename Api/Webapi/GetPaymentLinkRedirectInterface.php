<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Api\Webapi;

use Mollie\Payment\Api\Data\PaymentLinkRedirectResultInterface;

interface GetPaymentLinkRedirectInterface
{
    /**
     * @param string $hash
     * @return \Mollie\Payment\Api\Data\PaymentLinkRedirectResultInterface
     */
    public function byHash(string $hash): PaymentLinkRedirectResultInterface;
}
