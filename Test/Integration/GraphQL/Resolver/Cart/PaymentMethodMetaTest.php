<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\GraphQL\Resolver\Cart;

use Mollie\Payment\Test\Integration\GraphQLTestCase;

/**
 * @magentoAppArea graphql
 */
class PaymentMethodMetaTest extends GraphQLTestCase
{
    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Sales/_files/quote_with_customer.php
     */
    public function testReturnsAnEmptyResponseForNonMollieMethods()
    {
        $result = $this->getMethodFromCart('checkmo');

        $this->assertNull($result['mollie_meta']['image']);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Sales/_files/quote_with_customer.php
     * @magentoConfigFixture default_store payment/mollie_general/enabled 1
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/active 1
     */
    public function testReturnsTheImageForMollieMethods()
    {
        $result = $this->getMethodFromCart('mollie_methods_ideal');

        $this->assertStringContainsString(
            'Mollie_Payment/images/methods/ideal.svg',
            $result['mollie_meta']['image']
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Sales/_files/quote_with_customer.php
     * @magentoConfigFixture default_store payment/mollie_general/enabled 1
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/active 1
     */
    public function testTheImagesIsAFrontendPath()
    {
        $result = $this->getMethodFromCart('mollie_methods_ideal');

        $this->assertStringContainsString('frontend/Magento/luma', $result['mollie_meta']['image']);
    }

    /**
     * @throws \Exception
     * @return array
     */
    public function getMethodFromCart(string $method): array
    {
        $this->loadFakeEncryptor()->addReturnValue('', 'test_dummyapikeythatisvalidandislongenough');
        $this->loadPaymentMethodManagementPluginFake()->returnAll();

        $cartId = $this->prepareCustomerCartWithoutPayment();

        $result = $this->graphQlQuery('query {
            cart(cart_id: "' . $cartId . '") {
                available_payment_methods {
                    code
                    mollie_meta {
                        image
                    }
                }
            }
        }');

        foreach ($result['cart']['available_payment_methods'] as $paymentMethod) {
            if ($paymentMethod['code'] == $method) {
                return $paymentMethod;
            }
        }

        $this->fail(sprintf('Method %s not found in available_payment_methods', $method));
    }
}
