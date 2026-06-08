<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\GraphQL\Resolver\Checkout;

use Exception;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Mollie\Payment\Service\Mollie\GetMollieStatusResult;
use Mollie\Payment\Service\Mollie\ProcessTransaction;
use Mollie\Payment\Service\PaymentToken\Generate;
use Mollie\Payment\Test\Fakes\Service\Mollie\ProcessTransactionFake;
use Mollie\Payment\Test\Integration\GraphQLTestCase;

/**
 * @magentoAppArea graphql
 */
class ProcessTransactionTest extends GraphQLTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testResetsTheCartWhenPending(): void
    {
        /** @var CartInterface $cart */
        $cart = $this->objectManager->create(Quote::class);
        $cart->load('test01', 'reserved_order_id');
        $cart->setIsActive(0);
        $cart->save();

        $order = $this->loadOrder('100000001');
        $order->setQuoteId($cart->getId());
        $order->setMollieTransactionId('tr_123');
        $order->save();

        $tokenModel = $this->objectManager->get(Generate::class)->forOrder($order);

        $fake = $this->objectManager->create(ProcessTransactionFake::class);
        $fake->setResponse($this->objectManager->create(
            GetMollieStatusResult::class,
            ['status' => 'failed', 'method' => 'ideal'],
        ));
        $this->objectManager->addSharedInstance($fake, ProcessTransaction::class);

        $result = $this->graphQlQuery('mutation {
            mollieProcessTransaction(input: { payment_token: "' . $tokenModel->getToken() . '" }) {
                paymentStatus
            }
        }');

        $this->assertEquals('FAILED', $result['mollieProcessTransaction']['paymentStatus']);

        $newCart = $this->objectManager->create(CartRepositoryInterface::class)->get($tokenModel->getCartId());
        $newCart->load('test01', 'reserved_order_id');
        $this->assertTrue((bool) $newCart->getIsActive());
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testDoesNotReactivateTheCartWhenTheStatusIsPending(): void
    {
        /** @var CartInterface $cart */
        $cart = $this->objectManager->create(Quote::class);
        $cart->load('test01', 'reserved_order_id');
        $cart->setIsActive(0);
        $cart->save();

        $order = $this->loadOrder('100000001');
        $order->setQuoteId($cart->getId());
        $order->setMollieTransactionId('tr_123');
        $order->save();

        $tokenModel = $this->objectManager->get(Generate::class)->forOrder($order);

        $fake = $this->objectManager->create(ProcessTransactionFake::class);
        $fake->setResponse($this->objectManager->create(
            GetMollieStatusResult::class,
            ['status' => 'pending', 'method' => 'ideal'],
        ));
        $this->objectManager->addSharedInstance($fake, ProcessTransaction::class);

        $result = $this->graphQlQuery('mutation {
            mollieProcessTransaction(input: { payment_token: "' . $tokenModel->getToken() . '" }) {
                paymentStatus
            }
        }');

        $this->assertEquals('PENDING', $result['mollieProcessTransaction']['paymentStatus']);

        $newCart = $this->objectManager->create(CartRepositoryInterface::class)->get($tokenModel->getCartId());
        $newCart->load('test01', 'reserved_order_id');
        $this->assertFalse((bool) $newCart->getIsActive());
    }

    /**
     * @throws Exception
     * @magentoAppArea graphql
     */
    public function testThrowsANotFoundExceptionWhenTokenDoesNotExists(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('GraphQL response contains errors: No order found with token "non-existing-payment-token"');

        $this->graphQlQuery('mutation {
          mollieProcessTransaction(input: {
              payment_token: "non-existing-payment-token"
          }) {
            paymentStatus
          }
        }');
    }
}
