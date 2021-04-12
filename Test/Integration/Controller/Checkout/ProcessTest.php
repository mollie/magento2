<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Controller\Checkout;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Message\MessageInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\TestFramework\TestCase\AbstractController;
use Mollie\Payment\Model\Mollie;

class ProcessTest extends AbstractController
{
    public function testDoesRedirectsToCartWhenNoIdProvided()
    {
        $this->dispatch('mollie/checkout/process');

        $this->assertRedirect($this->stringContains('checkout/cart'));
        $this->assertSessionMessages($this->equalTo(['Invalid return from Mollie.']), MessageInterface::TYPE_NOTICE);
    }

    public function testRedirectsToCartOnException()
    {
        $mollieModel = $this->createMock(Mollie::class);
        $mollieModel->method('processTransaction')->willThrowException(new \Exception('[TEST] Something went wrong'));

        $this->_objectManager->addSharedInstance($mollieModel, Mollie::class);

        $this->dispatch('mollie/checkout/process?order_id=123');

        $this->assertRedirect($this->stringContains('checkout/cart'));
        $this->assertSessionMessages(
            $this->equalTo(['There was an error checking the transaction status.']),
            MessageInterface::TYPE_ERROR
        );
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testUsesOrderIdParameter()
    {
        $order = $this->loadOrderById('100000001');

        $mollieModel = $this->createMock(Mollie::class);
        $mollieModel->expects($this->once())->method('processTransaction')->with($order->getId())->willReturn([]);

        $this->_objectManager->addSharedInstance($mollieModel, Mollie::class);

        $this->dispatch('mollie/checkout/process?order_id=' . $order->getId());
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order_list.php
     */
    public function testUsesOrderIdsParameter()
    {
        $order1 = $this->loadOrderById('100000001');
        $order2 = $this->loadOrderById('100000002');
        $order3 = $this->loadOrderById('100000003');
        $order4 = $this->loadOrderById('100000004');

        $mollieModel = $this->createMock(Mollie::class);
        $mollieModel->expects($this->exactly(4))->method('processTransaction')->willReturn([]);

        $this->_objectManager->addSharedInstance($mollieModel, Mollie::class);

        $queryString = [
            'order_ids[]=' . $order1->getId(),
            'order_ids[]=' . $order2->getId(),
            'order_ids[]=' . $order3->getId(),
            'order_ids[]=' . $order4->getId(),
        ];

        $this->dispatch('mollie/checkout/process?' . implode('&', $queryString));
    }

    /**
     * @param $orderId
     * @return OrderInterface
     */
    private function loadOrderById($orderId)
    {
        $repository = $this->_objectManager->get(OrderRepositoryInterface::class);
        $builder = $this->_objectManager->create(SearchCriteriaBuilder::class);
        $searchCriteria = $builder->addFilter('increment_id', $orderId, 'eq')->create();

        $orderList = $repository->getList($searchCriteria)->getItems();

        return array_shift($orderList);
    }
}
