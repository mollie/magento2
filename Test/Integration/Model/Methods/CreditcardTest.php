<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 *  * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Payment;
use Mollie\Payment\Model\Methods\Creditcard;

class CreditcardTest extends AbstractMethodTest
{
    protected $instance = Creditcard::class;

    protected $code = 'creditcard';

    public function testDoesNotSendEmailsWhenPlacingAnOrder()
    {
        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);

        /** @var Payment $paymentInfo */
        $paymentInfo = $this->objectManager->create(Payment::class);
        $paymentInfo->setOrder($order);

        /** @var Creditcard $instance */
        $instance = $this->objectManager->create(Creditcard::class);

        $this->assertFalse($paymentInfo->getIsTransactionPending());
        $this->assertTrue($order->getCanSendNewEmailFlag());

        $instance->authorize($paymentInfo, '999.99');

        $this->assertTrue($paymentInfo->getIsTransactionPending());
        $this->assertFalse($order->getCanSendNewEmailFlag());
    }
}
