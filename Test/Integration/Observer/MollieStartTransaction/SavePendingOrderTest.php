<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Observer\MollieStartTransaction;

use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Api\PendingPaymentReminderRepositoryInterface;
use Mollie\Payment\Observer\MollieStartTransaction\SavePendingOrder;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class SavePendingOrderTest extends IntegrationTestCase
{
    /**
     * @dataProvider asyncMethodProvider
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/mollie_general/enable_second_chance_email 1
     * @magentoConfigFixture default_store payment/mollie_general/automatically_send_second_chance_emails 1
     */
    #[DataProvider('asyncMethodProvider')]
    public function testDoesNotSaveAReminderForAnAsyncMethod(string $method): void
    {
        $order = $this->executeForMethod($method);

        $this->expectException(NoSuchEntityException::class);

        $this->objectManager->get(PendingPaymentReminderRepositoryInterface::class)
            ->getByOrderId((int) $order->getEntityId());
    }

    public static function asyncMethodProvider(): array
    {
        return [
            'banktransfer' => ['mollie_methods_banktransfer'],
            'paybybank' => ['mollie_methods_paybybank'],
        ];
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/mollie_general/enable_second_chance_email 1
     * @magentoConfigFixture default_store payment/mollie_general/automatically_send_second_chance_emails 1
     */
    public function testSavesAReminderForASynchronousMethod(): void
    {
        $order = $this->executeForMethod('mollie_methods_ideal');

        $reminder = $this->objectManager->get(PendingPaymentReminderRepositoryInterface::class)
            ->getByOrderId((int) $order->getEntityId());

        $this->assertEquals($order->getEntityId(), $reminder->getOrderId());
    }

    private function executeForMethod(string $method): OrderInterface
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod($method);

        $observer = $this->objectManager->create(Observer::class);
        $observer->setData('order', $order);

        $this->objectManager->create(SavePendingOrder::class)->execute($observer);

        return $order;
    }
}
