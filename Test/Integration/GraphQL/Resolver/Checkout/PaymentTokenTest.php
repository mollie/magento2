<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\GraphQL\Resolver\Checkout;

use Magento\Sales\Api\OrderRepositoryInterface;
use Mollie\Payment\Api\PaymentTokenRepositoryInterface;
use Mollie\Payment\Test\Integration\GraphQLTestCase;

/**
 * @magentoAppArea graphql
 */
class PaymentTokenTest extends GraphQLTestCase
{
    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Sales/_files/quote_with_customer.php
     * @magentoConfigFixture default_store payment/mollie_general/enabled 1
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/active 1
     * @magentoConfigFixture default_store payment/mollie_general/mode test
     * @magentoConfigFixture default_store payment/mollie_general/apikey_test test_dummyapikeywhichmustbe30characterslong
     */
    public function testGeneratesAPaymentTokenWhenAnOrderIsPlaced(): void
    {
        $result = $this->placeOrder();

        $token = $result['placeOrder']['order']['mollie_payment_token'];
        $this->assertNotEmpty($token);

        $tokenRepository = $this->objectManager->get(PaymentTokenRepositoryInterface::class);
        $model = $tokenRepository->getByToken($token);

        $orderRepository = $this->objectManager->get(OrderRepositoryInterface::class);
        $order = $orderRepository->get($model->getOrderId());

        $this->assertEquals($result['placeOrder']['order']['order_number'], $order->getIncrementId());
    }

    /**
     * @throws \Exception
     * @return array
     */
    public function placeOrder(): array
    {
        $this->loadFakeEncryptor()->addReturnValue('', 'test_dummyapikeythatisvalidandislongenough');
        $this->loadPaymentMethodManagementPluginFake()->returnAll();

        $cartId = $this->prepareCustomerCart();

        return $this->graphQlQuery('mutation cart {
            placeOrder(input: {
                cart_id:"' . $cartId . '"
            }) {
                order {
                    mollie_payment_token
                    order_number
                }
            }
        }');
    }
}
