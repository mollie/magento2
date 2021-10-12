<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\GraphQL\Resolver\Checkout;

use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Mollie\Payment\Model\Mollie;
use Mollie\Payment\Service\PaymentToken\Generate;
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
    public function testResetsTheCartWhenPending()
    {
        /** @var CartInterface $cart */
        $cart = $this->objectManager->create(Quote::class);
        $cart->load('test01', 'reserved_order_id');
        $cart->setIsActive(0);
        $cart->save();

        $order = $this->loadOrder('100000001');
        $order->setQuoteId($cart->getId());

        $tokenModel = $this->objectManager->get(Generate::class)->forOrder($order);
        $mollieMock = $this->createMock(Mollie::class);
        $mollieMock->method('processTransaction')->willReturn(['status' => 'pending']);
        $this->objectManager->addSharedInstance($mollieMock, Mollie::class);

        $result = $this->graphQlQuery('mutation {
            mollieProcessTransaction(input: { payment_token: "' . $tokenModel->getToken() . '" }) {
                paymentStatus
            }
        }');

        $this->assertEquals('PENDING', $result['mollieProcessTransaction']['paymentStatus']);

        $newCart = $this->objectManager->create(CartRepositoryInterface::class)->get($tokenModel->getCartId());
        $newCart->load('test01', 'reserved_order_id');
        $this->assertTrue((bool)$newCart->getIsActive());
    }

    /**
     * @throws \Exception
     * @magentoAppArea graphql
     */
    public function testThrowsANotFoundExceptionWhenTokenDoesNotExists()
    {
        $this->expectException(\Exception::class);
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
