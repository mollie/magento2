<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\AuthorizationException;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Service\Mollie\ValidateProcessRequest;
use Mollie\Payment\Service\PaymentToken\PaymentTokenForOrder;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class ValidateProcessRequestTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoDataFixture Magento/Sales/_files/quote.php
     */
    public function testValidatesWithASinglePaymentToken(): void
    {
        $order = $this->getOrder();

        $paymentTokenForOrder = $this->objectManager->get(PaymentTokenForOrder::class);
        $token = $paymentTokenForOrder->execute($order);

        $request = $this->objectManager->create(RequestInterface::class);
        $request->setParam('order_id', $order->getId());
        $request->setParam('payment_token', $token);

        $instance = $this->objectManager->create(ValidateProcessRequest::class, [
            'request' => $request,
        ]);

        $result = $instance->execute();

        $this->assertEquals([$order->getId() => $token], $result);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order_list.php
     * @magentoDataFixture Magento/Sales/_files/quote.php
     */
    public function testValidatesWithMultiplePaymentTokens(): void
    {
        $orders = [
            $this->getOrder('100000002'),
            $this->getOrder('100000003'),
            $this->getOrder('100000004'),
        ];

        $orderIds = [];
        $paymentTokens = [];
        foreach ($orders as $order) {
            $paymentTokenForOrder = $this->objectManager->get(PaymentTokenForOrder::class);
            $orderIds[] = $order->getEntityId();
            $paymentTokens[] = $paymentTokenForOrder->execute($order);
        }

        $request = $this->objectManager->create(RequestInterface::class);
        $request->setParam('order_ids', $orderIds);
        $request->setParam('payment_tokens', $paymentTokens);

        $instance = $this->objectManager->create(ValidateProcessRequest::class, [
            'request' => $request,
        ]);

        $result = $instance->execute();

        $this->assertEquals([
            $orders[0]->getId() => $paymentTokens[0],
            $orders[1]->getId() => $paymentTokens[1],
            $orders[2]->getId() => $paymentTokens[2],
        ], $result);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoDataFixture Magento/Sales/_files/quote.php
     */
    public function testFailsWhenTheOrderIdIsWrong(): void
    {
        $order = $this->getOrder();

        $paymentTokenForOrder = $this->objectManager->get(PaymentTokenForOrder::class);
        $token = $paymentTokenForOrder->execute($order);

        $request = $this->objectManager->create(RequestInterface::class);
        $request->setParam('order_ids', [999]);
        $request->setParam('payment_tokens', [$token]);

        $instance = $this->objectManager->create(ValidateProcessRequest::class, [
            'request' => $request,
        ]);

        $this->expectException(AuthorizationException::class);

        $instance->execute();
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoDataFixture Magento/Sales/_files/quote.php
     */
    public function testFailsWhenTheOrdersDontMatchTheTokens(): void
    {
        $order = $this->getOrder();

        $paymentTokenForOrder = $this->objectManager->get(PaymentTokenForOrder::class);
        $token = $paymentTokenForOrder->execute($order);

        $request = $this->objectManager->create(RequestInterface::class);
        $request->setParam('order_ids', [888, $order->getId()]);
        $request->setParam('payment_tokens', [$token]);

        $instance = $this->objectManager->create(ValidateProcessRequest::class, [
            'request' => $request,
        ]);

        $this->expectException(AuthorizationException::class);

        $instance->execute();
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order_list.php
     * @magentoDataFixture Magento/Sales/_files/quote.php
     */
    public function testFailsWhenTheTokensDontMatchTheOrders(): void
    {
        $orders = [
            $this->getOrder('100000002'),
            $this->getOrder('100000003'),
        ];

        $paymentTokenForOrder = $this->objectManager->get(PaymentTokenForOrder::class);
        $tokens = [
            $paymentTokenForOrder->execute($orders[0]),
            $paymentTokenForOrder->execute($orders[1]),
        ];

        $request = $this->objectManager->create(RequestInterface::class);
        $request->setParam('order_ids', [$orders[0]->getId()]); // Only 1 order, should be 2.
        $request->setParam('payment_tokens', $tokens);

        $instance = $this->objectManager->create(ValidateProcessRequest::class, [
            'request' => $request,
        ]);

        $this->expectException(AuthorizationException::class);

        $instance->execute();
    }

    private function getOrder(string $orderId = '100000001'): OrderInterface
    {
        $cart = $this->objectManager->create(Quote::class);
        $cart->load('test01', 'reserved_order_id');

        $order = $this->loadOrder($orderId);
        $order->setQuoteId($cart->getId());
        return $order;
    }
}
