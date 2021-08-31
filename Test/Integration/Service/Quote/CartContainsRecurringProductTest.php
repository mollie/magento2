<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Service\Quote;

use Magento\Checkout\Model\Session;
use Magento\Framework\Serialize\SerializerInterface;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use Mollie\Payment\Service\Quote\CartContainsRecurringProduct;

class CartContainsRecurringProductTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Checkout/_files/quote_with_simple_product.php
     */
    public function testReturnsFalseWhenNoItemAvailable()
    {
        /** @var \Magento\Quote\Model\Quote $cart */
        $cart = $this->objectManager->create(Session::class)->getQuote();

        /** @var CartContainsRecurringProduct $instance */
        $instance = $this->objectManager->create(CartContainsRecurringProduct::class);

        $this->assertFalse($instance->execute($cart), 'The default cart should not contain a subscription product');
    }

    /**
     * @magentoDataFixture Magento/Checkout/_files/quote_with_simple_product_and_custom_option.php
     */
    public function testReturnsTrueWhenOneOfTheItemsIsASubscriptionProduct()
    {
        /** @var SerializerInterface $serializer */
        $serializer = $this->objectManager->create(SerializerInterface::class);

        /** @var \Magento\Quote\Model\Quote $cart */
        $cart = $this->objectManager->create(Session::class)->getQuote();
        $items = $cart->getItems();
        $item = array_shift($items);

        $item->addOption([
            'code' => 'info_buyRequest',
            'value' => $serializer->serialize([
                'qty' => 1,
                'mollie_metadata' => [
                    'is_recurring' => 1,
                ]
            ]),
        ]);

        /** @var CartContainsRecurringProduct $instance */
        $instance = $this->objectManager->create(CartContainsRecurringProduct::class);

        $this->assertTrue($instance->execute($cart), 'The cart should contain a subscription product');
    }
}
