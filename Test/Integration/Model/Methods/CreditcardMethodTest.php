<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Payment;
use Mollie\Payment\Model\Methods\Creditcard;

class CreditcardMethodTest extends AbstractTestMethod
{
    protected ?string $instance = Creditcard::class;

    protected ?string $code = 'creditcard';

    public function testDoesNotSendEmailsWhenPlacingAnOrder(): void
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
