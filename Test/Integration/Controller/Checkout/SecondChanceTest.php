<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Controller\Checkout;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\TestFramework\TestCase\AbstractController;
use Mollie\Payment\Service\Order\Reorder;
use Mollie\Payment\Service\PaymentToken\Generate;

class SecondChanceTest extends AbstractController
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testThrowsNotFoundExceptionWhenTheTokenIsIncorrect()
    {
        $this->expectException(\Magento\Framework\Exception\NoSuchEntityException::class);

        $order = $this->loadOrder('100000001');

        $this->getRequest()->setParam('order_id', $order->getId());
        $this->getRequest()->setParam('payment_token', 'randomstring');

        $this->dispatch('/mollie/checkout/secondChance/');
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testRedirectsToTheCheckoutUrl()
    {
        $cart = $this->_objectManager->create(Quote::class);
        $cart->load('test01', 'reserved_order_id');

        $order = $this->loadOrder('100000001');
        $order->setState(Order::STATE_NEW);
        $order->setQuoteId($cart->getId());
        $order->getPayment()->setAdditionalInformation('checkout_url', 'http://example.com');
        $this->_objectManager->get(OrderRepositoryInterface::class)->save($order);

        /** @var Generate $generate */
        $generate = $this->_objectManager->get(Generate::class);
        $token = $generate->forOrder($order);

        $this->getRequest()->setParam('order_id', $order->getId());
        $this->getRequest()->setParam('payment_token', $token->getToken());

        $this->dispatch('/mollie/checkout/secondChance/');

        $this->assertRedirect($this->equalTo('http://example.com'));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testRecreatesTheOrder()
    {
        $order = $this->loadOrder('100000001');

        $reorderMock = $this->createMock(Reorder::class);
        $reorderMock->expects($this->once())->method('create')->willReturn($order);
        $this->_objectManager->addSharedInstance($reorderMock, Reorder::class);

        $cart = $this->_objectManager->create(Quote::class);
        $cart->load('test01', 'reserved_order_id');

        $order->setState(Order::STATE_PROCESSING);
        $order->setQuoteId($cart->getId());
        $order->getPayment()->setAdditionalInformation('checkout_url', 'http://example.com');
        $this->_objectManager->get(OrderRepositoryInterface::class)->save($order);

        /** @var Generate $generate */
        $generate = $this->_objectManager->get(Generate::class);
        $token = $generate->forOrder($order);

        $this->getRequest()->setParam('order_id', $order->getId());
        $this->getRequest()->setParam('payment_token', $token->getToken());

        $this->dispatch('/mollie/checkout/secondChance/');
        $this->assertRedirect($this->equalTo('http://example.com'));
    }

    /**
     * @param $incrementId
     * @return OrderInterface
     */
    private function loadOrder($incrementId)
    {
        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->_objectManager->create(SearchCriteriaBuilder::class);

        /** @var OrderRepositoryInterface $order */
        $orderRepository = $this->_objectManager->create(OrderRepositoryInterface::class);

        $searchCriteria = $searchCriteriaBuilder->addFilter('increment_id', $incrementId, 'eq')->create();
        $orderList = $orderRepository->getList($searchCriteria)->getItems();

        return array_shift($orderList);
    }
}
