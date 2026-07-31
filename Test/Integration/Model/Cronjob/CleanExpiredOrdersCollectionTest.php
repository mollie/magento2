<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Model\Cronjob;

use Magento\Sales\Api\OrderRepositoryInterface;
use Mollie\Payment\Model\Cronjob\CleanExpiredOrdersCollection;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class CleanExpiredOrdersCollectionTest extends IntegrationTestCase
{
    /**
     * @dataProvider asyncMethodProvider
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    #[DataProvider('asyncMethodProvider')]
    public function testExcludesOrdersPaidWithAnAsyncMethod(string $method): void
    {
        $orderId = $this->createOrderWithMethod($method);

        $this->assertNotContains($orderId, $this->getAllIds());
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
     */
    public function testIncludesOrdersPaidWithASynchronousMethod(): void
    {
        $orderId = $this->createOrderWithMethod('mollie_methods_ideal');

        $this->assertContains($orderId, $this->getAllIds());
    }

    private function createOrderWithMethod(string $method): string
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod($method);

        $this->objectManager->get(OrderRepositoryInterface::class)->save($order);

        return (string) $order->getEntityId();
    }

    /**
     * @return string[]
     */
    private function getAllIds(): array
    {
        /** @var CleanExpiredOrdersCollection $collection */
        $collection = $this->objectManager->create(CleanExpiredOrdersCollection::class);

        return array_map('strval', $collection->getAllIds());
    }
}
