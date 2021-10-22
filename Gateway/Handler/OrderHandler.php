<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Gateway\Handler;

use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Payment\Gateway\Response\HandlerInterface;

class OrderHandler implements HandlerInterface
{
    public function handle(array $handlingSubject, array $response)
    {
        /** @var PaymentDataObject $paymentDataObject */
        $paymentDataObject = $handlingSubject['payment'];
        $payment = $paymentDataObject->getPayment();
        $order = $payment->getOrder();

        $order->setCanSendNewEmailFlag(false);
    }
}
