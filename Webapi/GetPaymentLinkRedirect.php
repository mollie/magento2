<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Webapi;

use Mollie\Payment\Api\Data\PaymentLinkRedirectResultInterface;
use Mollie\Payment\Api\Webapi\GetPaymentLinkRedirectInterface;
use Mollie\Payment\Service\Magento\PaymentLinkRedirect as PaymentLinkRedirectService;

class GetPaymentLinkRedirect implements GetPaymentLinkRedirectInterface
{
    public function __construct(
        private PaymentLinkRedirectService $paymentLinkRedirect
    ) {}

    public function byHash(string $hash): PaymentLinkRedirectResultInterface
    {
        return $this->paymentLinkRedirect->execute($hash);
    }
}
