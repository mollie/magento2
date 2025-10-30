<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Gateway\Handler;

use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Api\Data\OrderInterface;

class OrderHandler implements HandlerInterface
{
    public function handle(array $handlingSubject, array $response): void
    {
        /** @var PaymentDataObject $paymentDataObject */
        $paymentDataObject = $handlingSubject['payment'];
        $payment = $paymentDataObject->getPayment();

        // Make sure the transaction is marked as pending so we don't get the wrong order state.
        $payment->setIsTransactionPending(true);

        /** @var OrderInterface $order */
        $order = $payment->getOrder();

        // Don't send the email just yet.
        $order->setCanSendNewEmailFlag(false);
    }
}
