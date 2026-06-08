<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Magento;

use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Mollie\Payment\Service\Magento\PaymentLinkRedirect;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class PaymentLinkRedirectTest extends IntegrationTestCase
{
    public function testThrowsExceptionWhenOrderDoesNotExists(): void
    {
        $this->expectException(NotFoundException::class);

        $encryptor = $this->objectManager->get(EncryptorInterface::class);
        $orderId = base64_encode($encryptor->encrypt('random string'));

        /** @var PaymentLinkRedirect $instance */
        $instance = $this->objectManager->create(PaymentLinkRedirect::class);

        $instance->execute($orderId);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     *
     * @return void
     */
    public function testDoesNotRedirectWhenOrderAlreadyPaid(): void
    {
        $order = $this->loadOrder('100000001');
        $order->setState(Order::STATE_PROCESSING);

        $encryptor = $this->objectManager->get(EncryptorInterface::class);
        $orderId = base64_encode($encryptor->encrypt($order->getEntityId()));

        /** @var PaymentLinkRedirect $instance */
        $instance = $this->objectManager->create(PaymentLinkRedirect::class);
        $result = $instance->execute($orderId);

        $this->assertTrue($result->isAlreadyPaid());
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     *
     * @return void
     */
    public function testDoesNotRedirectWhenExpired(): void
    {
        $orderRepository = $this->objectManager->get(OrderRepositoryInterface::class);

        $order = $this->loadOrder('100000001');
        $order->setState(Order::STATE_PENDING_PAYMENT);
        $order->setCreatedAt(date('Y-m-d H:i:s', strtotime('-31 days')));
        $orderRepository->save($order);

        $encryptor = $this->objectManager->get(EncryptorInterface::class);
        $orderId = base64_encode($encryptor->encrypt($order->getEntityId()));

        /** @var PaymentLinkRedirect $instance */
        $instance = $this->objectManager->create(PaymentLinkRedirect::class);
        $result = $instance->execute($orderId);

        $this->assertTrue($result->isExpired());
    }
}
