<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\Order;

use Mollie\Payment\Service\Checkout\PaymentMethodMessages;
use Mollie\Payment\Service\Mollie\GetMollieStatusResult;

class AddResultMessage
{
    public function __construct(
        private PaymentMethodMessages $paymentMethodMessages
    ) {}

    public function execute(GetMollieStatusResult $result): void
    {
        if ($result->getStatus() == 'canceled') {
            $this->paymentMethodMessages->addNotice(__('Payment canceled, please try again.'));

            return;
        }

        $this->paymentMethodMessages->addError(__('Transaction failed. Please verify your billing information and payment method, and try again.'));
    }
}
