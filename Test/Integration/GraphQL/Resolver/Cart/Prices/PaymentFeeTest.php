<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\GraphQL\Resolver\Cart\Prices;

use Exception;
use Mollie\Payment\Test\Integration\GraphQLTestCase;

class PaymentFeeTest extends GraphQLTestCase
{
    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Sales/_files/quote_with_customer.php
     * @magentoConfigFixture default_store payment/mollie_general/enabled 1
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/active 1
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/payment_surcharge_type fixed_fee
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/payment_surcharge_fixed_amount 5
     * @magentoConfigFixture default_store payment/mollie_general/mode test
     * @magentoConfigFixture default_store payment/mollie_general/apikey_test test_dummyapikeywhichmustbe30characterslong
     * @magentoAppArea graphql
     */
    public function testReturnsPaymentFeePricesForMollieMethod(): void
    {
        $result = $this->callGraphQlQuery();

        $this->assertEquals(5, $result['cart']['prices']['mollie_payment_fee']['fee']['value']);
        $this->assertEquals('USD', $result['cart']['prices']['mollie_payment_fee']['fee']['currency']);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Sales/_files/quote_with_customer.php
     * @magentoConfigFixture default_store payment/mollie_general/enabled 1
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/active 1
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/payment_surcharge_type
     * @magentoConfigFixture default_store payment/mollie_general/mode test
     * @magentoConfigFixture default_store payment/mollie_general/apikey_test test_dummyapikeywhichmustbe30characterslong
     * @magentoAppArea graphql
     */
    public function testDoesNotReturnPricesWhenNoPaymentFeeAvailable(): void
    {
        $result = $this->callGraphQlQuery();

        $this->assertEquals(0, $result['cart']['prices']['mollie_payment_fee']['fee']['value']);
        $this->assertEquals('USD', $result['cart']['prices']['mollie_payment_fee']['fee']['currency']);
    }

    /**
     * @throws Exception
     * @return mixed
     */
    public function callGraphQlQuery(): array
    {
        $cartId = $this->prepareCustomerCart();

        return $this->graphQlQuery('
            query {
                cart(cart_id: "' . $cartId . '") {
                    prices {
                        mollie_payment_fee {
                            fee {
                                value
                                currency
                            }
                            base_fee {
                                value
                                currency
                            }
                            fee_tax {
                                value
                                currency
                            }
                            base_fee_tax {
                                value
                                currency
                            }
                        }
                    }
                }
            }
        ');
    }
}
