<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\GraphQL\Resolver\Cart;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface;
use Mollie\Payment\Test\Integration\GraphQLTestCase;

class ResetCartTest extends GraphQLTestCase
{
    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoAppArea graphql
     */
    public function testWorksWithInactiveCarts(): void
    {
        /** @var CartInterface $cart */
        $cart = $this->objectManager->create(Quote::class);
        $cart->load('test01', 'reserved_order_id');
        $cart->setIsActive(0);
        $cart->save();

        $quoteIdToMaskedQuoteId = $this->objectManager->get(QuoteIdToMaskedQuoteIdInterface::class);

        $result = $this->graphQlQuery('
            mutation {
              mollieRestoreCart(input: {
                  cart_id: "' . $quoteIdToMaskedQuoteId->execute($cart->getId()) . '"
              }) {
                cart {
                    id
                }
              }
            }
        ');

        $this->assertNotEmpty($result['mollieRestoreCart']['cart']['id']);
    }
    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Sales/_files/quote_with_customer.php
     * @magentoAppArea graphql
     */
    public function testChecksTheCustomer(): void
    {
        $this->expectExceptionMessageMatches('/The current user cannot perform operations on cart/');

        /** @var CartInterface $cart */
        $cart = $this->objectManager->create(Quote::class);
        $cart->load('test01', 'reserved_order_id');

        $quoteIdToMaskedQuoteId = $this->objectManager->get(QuoteIdToMaskedQuoteIdInterface::class);

        $this->graphQlQuery('
            mutation {
              mollieRestoreCart(input: {
                  cart_id: "' . $quoteIdToMaskedQuoteId->execute($cart->getId()) . '"
              }) {
                cart {
                    id
                }
              }
            }
        ');
    }
}
