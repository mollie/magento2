<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Controller\Checkout;

use Magento\Checkout\Model\Session;
use Exception;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Message\MessageInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Magento\TestFramework\TestCase\AbstractController;
use Mollie\Payment\Model\Methods\Ideal;
use Mollie\Payment\Service\Mollie\Order\RedirectUrl;
use Mollie\Payment\Service\PaymentToken\Generate;
use Mollie\Payment\Test\Fakes\Service\Mollie\Order\RedirectUrlFake;

class RedirectTest extends AbstractController
{
    private const MOLLIE_REDIRECT_URL = 'https://www.mollie.com/checkout/test-redirect';

    public function testRedirectsToCartWhenThereIsNoTokenAndNoOrderInTheSession(): void
    {
        $this->dispatch('mollie/checkout/redirect');

        $this->assertRedirect($this->stringContains('checkout/cart'));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testUsesTheOrderOfTheSessionWhenTheTokenIsMissing(): void
    {
        $order = $this->createMollieOrderAwaitingPayment();
        $this->addOrderToSession($order);
        $this->assertRedirectUrlIsBuiltForOrder($order);

        $this->dispatch('mollie/checkout/redirect');

        $this->assertRedirect($this->stringContains(static::MOLLIE_REDIRECT_URL));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testUsesTheOrderOfTheSessionWhenTheTokenIsUnknown(): void
    {
        $order = $this->createMollieOrderAwaitingPayment();
        $this->addOrderToSession($order);
        $this->assertRedirectUrlIsBuiltForOrder($order);

        $this->dispatch('mollie/checkout/redirect/paymentToken/undefined');

        $this->assertRedirect($this->stringContains(static::MOLLIE_REDIRECT_URL));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testUsesTheOrderOfTheTokenWhenOneIsProvided(): void
    {
        $order = $this->createMollieOrderAwaitingPayment();
        $order->setQuoteId($this->loadQuoteOfFixture()->getId());

        $token = $this->_objectManager->create(Generate::class)->forOrder($order);
        $this->assertRedirectUrlIsBuiltForOrder($order);

        $this->dispatch('mollie/checkout/redirect/paymentToken/' . $token->getToken());

        $this->assertRedirect($this->stringContains(static::MOLLIE_REDIRECT_URL));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testRedirectsToCartWhenTheOrderOfTheSessionIsNotAwaitingPayment(): void
    {
        $order = $this->createMollieOrderAwaitingPayment();
        $order->setState(Order::STATE_PROCESSING);
        $this->_objectManager->get(OrderRepositoryInterface::class)->save($order);

        $this->addOrderToSession($order);

        $redirectUrl = $this->createMock(RedirectUrl::class);
        $redirectUrl->expects($this->never())->method('execute');
        $this->_objectManager->addSharedInstance($redirectUrl, RedirectUrl::class);

        $this->dispatch('mollie/checkout/redirect');

        $this->assertRedirect($this->stringContains('checkout/cart'));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture current_store payment/mollie_general/redirect_when_transaction_fails_to redirect_to_checkout_payment
     */
    public function testRedirectsToTheConfiguredPageWithoutExposingTheErrorWhenStartingThePaymentFails(): void
    {
        $order = $this->createMollieOrderAwaitingPayment();
        $this->addOrderToSession($order);

        $redirectUrl = $this->_objectManager->create(RedirectUrlFake::class);
        $redirectUrl->givenStartingThePaymentFails(new Exception('Request body: {"email":"customer@example.com"}'));
        $this->_objectManager->addSharedInstance($redirectUrl, RedirectUrl::class);

        $this->dispatch('mollie/checkout/redirect');

        $this->assertRedirect($this->stringContains('checkout/#payment'));
        $this->assertSessionMessages(
            $this->equalTo(['Your payment was not completed. Please try again or select another payment method.']),
            MessageInterface::TYPE_ERROR,
        );
    }

    private function createMollieOrderAwaitingPayment(): OrderInterface
    {
        $repository = $this->_objectManager->get(OrderRepositoryInterface::class);
        $builder = $this->_objectManager->create(SearchCriteriaBuilder::class);
        $searchCriteria = $builder->addFilter('increment_id', '100000001', 'eq')->create();

        $orders = $repository->getList($searchCriteria)->getItems();
        $order = array_shift($orders);

        $order->getPayment()->setMethod(Ideal::CODE);
        $order->setState(Order::STATE_PENDING_PAYMENT);

        return $repository->save($order);
    }

    private function loadQuoteOfFixture(): Quote
    {
        $quote = $this->_objectManager->create(Quote::class);
        $quote->load('test01', 'reserved_order_id');

        return $quote;
    }

    private function addOrderToSession(OrderInterface $order): void
    {
        $this->_objectManager->get(Session::class)->setLastRealOrderId($order->getIncrementId());
    }

    private function assertRedirectUrlIsBuiltForOrder(OrderInterface $order): void
    {
        $redirectUrl = $this->createMock(RedirectUrl::class);
        $redirectUrl->expects($this->once())
            ->method('execute')
            ->with($this->anything(), $this->callback(
                fn (OrderInterface $subject) => $subject->getEntityId() === $order->getEntityId(),
            ))
            ->willReturn(static::MOLLIE_REDIRECT_URL);

        $this->_objectManager->addSharedInstance($redirectUrl, RedirectUrl::class);
    }
}
