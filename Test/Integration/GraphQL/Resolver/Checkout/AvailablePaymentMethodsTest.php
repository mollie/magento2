<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\GraphQL\Resolver\Checkout;

use Exception;
use Magento\Framework\Encryption\Encryptor;
use Magento\GraphQl\Quote\GetMaskedQuoteIdByReservedOrderId;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Mollie\Payment\Test\Integration\GraphQLTestCase;

class AvailablePaymentMethodsTest extends GraphQLTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @throws Exception
     * @magentoAppArea graphql
     *
     * @magentoConfigFixture default_store payment/mollie_general/enabled 1
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard_vault/active 1
     */
    public function testHidesTheVaultMethod(): void
    {
        $encryptorMock = $this->createMock(Encryptor::class);
        $encryptorMock->method('decrypt')->willReturn('test_dummyapikeywhichmustbe30characterslong');

        $this->objectManager->addSharedInstance($encryptorMock, Encryptor::class);

        /** @var CartInterface $cart */
        $cart = $this->objectManager->create(Quote::class);
        $cart->load('test01', 'reserved_order_id');
        $cart->setIsMultiShipping(false);
        $cart->save();

        $maskedQuoteId = $this->objectManager->get(GetMaskedQuoteIdByReservedOrderId::class)->execute('test01');

        $result = $this->graphQlQuery('query {
          cart(cart_id: "' . $maskedQuoteId . '") {
            available_payment_methods {
              code
              title
              mollie_meta {
                  image
              }
              mollie_available_issuers {
                name
                code
                image
                svg
              }
            }
          }
        }');

        $this->assertFalse(in_array(
            'mollie_methods_creditcard_vault',
            array_column($result['cart']['available_payment_methods'], 'code'),
        ));
    }
}
