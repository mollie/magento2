<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Gateway;

use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Info;
use Mollie\Payment\Gateway\Handler\OrderHandler;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class OrderHandlerTest extends IntegrationTestCase
{
    public function testMarksThePamentAsPending()
    {
        /** @var OrderHandler $instance */
        $instance = $this->objectManager->create(OrderHandler::class);

        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);

        /** @var Payment $paymentInfo */
        $paymentInfo = $this->objectManager->create(Payment::class);
        $paymentInfo->setOrder($order);

        /** @var PaymentDataObjectFactory $paymentDataObject */
        $paymentDataObject = $this->objectManager->get(PaymentDataObjectFactory::class);
        $paymentData = $paymentDataObject->create($paymentInfo);

        $payment = $paymentData->getPayment();
        $this->assertFalse($payment->getIsTransactionPending());

        $order = $payment->getOrder();
        $this->assertTrue($order->getCanSendNewEmailFlag());

        $instance->handle([
            'payment' => $paymentData,
        ], []);

        $this->assertTrue($payment->getIsTransactionPending());
        $this->assertFalse($order->getCanSendNewEmailFlag());
    }
}
