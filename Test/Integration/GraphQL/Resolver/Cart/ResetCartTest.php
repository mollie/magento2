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
                        items {
                            id
                        }
                    }
                }
            }
        ');

        $this->assertNotEmpty($result['mollieRestoreCart']['cart']['items']);
        $this->assertCount(1, $result['mollieRestoreCart']['cart']['items']);
    }
    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Sales/_files/quote_with_customer.php
     * @magentoAppArea graphql
     */
    public function testChecksTheCustomer(): void
    {
        /** @var CartInterface $cart */
        $cart = $this->objectManager->create(Quote::class);
        $cart->load('test01', 'reserved_order_id');

        $quoteIdToMaskedQuoteId = $this->objectManager->get(QuoteIdToMaskedQuoteIdInterface::class);

        try {
            $this->graphQlQuery('
                mutation {
                    mollieRestoreCart(input: {
                        cart_id: "' . $quoteIdToMaskedQuoteId->execute($cart->getId()) . '"
                    }) {
                        cart {
                            email
                        }
                    }
                }
            ');

            $this->fail('We expect that an exception is thrown.');
        } catch (\Exception $exception) {
            $this->assertStringContainsString(
                'The current user cannot perform operations on cart',
                $exception->getMessage()
            );
        }
    }
}
