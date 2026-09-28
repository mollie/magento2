<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\Order;

use Magento\Framework\Phrase;
use Mollie\Payment\Service\Checkout\PaymentMethodMessages;
use Mollie\Payment\Service\Mollie\GetMollieStatusResult;
use Mollie\Payment\Service\Mollie\PaymentFailure;

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

        $this->paymentMethodMessages->addError($this->getFailedMessage($result->getPaymentFailure()));
    }

    /**
     * For possible fraud we show our own neutral message, so an innocent customer is not told they are suspected
     * of fraud. All other failure messages are written by Mollie to be shown to the customer.
     */
    private function getFailedMessage(?PaymentFailure $paymentFailure): Phrase
    {
        if ($paymentFailure?->isPossibleFraud()) {
            return __('Your payment was declined. Please select another payment method.');
        }

        $message = $paymentFailure?->getMessage();
        if ($message !== null) {
            return new Phrase($message);
        }

        return __('Transaction failed. Please verify your billing information and payment method, and try again.');
    }
}
