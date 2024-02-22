<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Webapi;

use Mollie\Payment\Api\Data\PaymentLinkRedirectResultInterface;
use Mollie\Payment\Api\Webapi\GetPaymentLinkRedirectInterface;
use Mollie\Payment\Service\Magento\PaymentLinkRedirect as PaymentLinkRedirectService;

class GetPaymentLinkRedirect implements GetPaymentLinkRedirectInterface
{
    /**
     * @var PaymentLinkRedirectService
     */
    private $paymentLinkRedirect;

    public function __construct(
        PaymentLinkRedirectService $paymentLinkRedirect
    ) {
        $this->paymentLinkRedirect = $paymentLinkRedirect;
    }

    public function byHash(string $hash): PaymentLinkRedirectResultInterface
    {
        return $this->paymentLinkRedirect->execute($hash);
    }
}
