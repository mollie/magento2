<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Mollie\Payment\Model\Methods\CreditcardVault;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class CreditcardVaultTest extends IntegrationTestCase
{
    public function testDoesNotSendEmailsWhenPlacingAnOrder()
    {
        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);

        /** @var Payment $paymentInfo */
        $paymentInfo = $this->objectManager->create(Payment::class);
        $paymentInfo->setOrder($order);

        $paymentInfo->setAdditionalInformation([
            PaymentTokenInterface::PUBLIC_HASH => '123abc',
        ]);

        $tokenManagementMock = $this->createMock(PaymentTokenManagementInterface::class);
        $tokenManagementMock->method('getByPublicHash')->willReturn(
            $this->objectManager->create(PaymentTokenInterface::class)
        );

        /** @var CreditcardVault $instance */
        $instance = $this->objectManager->create(CreditcardVault::class, [
            'tokenManagement' => $tokenManagementMock,
        ]);

        $this->assertFalse($paymentInfo->getIsTransactionPending());
        $this->assertTrue($order->getCanSendNewEmailFlag());

        $instance->authorize($paymentInfo, '999.99');

        $this->assertTrue($paymentInfo->getIsTransactionPending());
        $this->assertFalse($order->getCanSendNewEmailFlag());
    }
}
