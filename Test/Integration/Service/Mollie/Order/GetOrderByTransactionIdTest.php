<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie\Order;

use Magento\TestFramework\Quote\Model\GetQuoteByReservedOrderId;
use Mollie\Payment\Service\Mollie\Order\ConvertComponentsPaymentToOrder;
use Mollie\Payment\Service\Mollie\Order\GetOrderByTransactionId;
use Mollie\Payment\Service\PaymentToken\PaymentTokenForQuote;
use Mollie\Payment\Test\Integration\ExpressPaymentBuilder;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class GetOrderByTransactionIdTest extends IntegrationTestCase
{
    /**
     * The express order is placed on a new quote, so the base cart id can never be used to find it back.
     * A webhook retry that cannot find the order places a second order for the same payment.
     *
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_and_shipping_method_saved.php
     */
    public function testFindsAnExpressOrderThatWasPlacedOnAnotherQuoteThanTheBaseCart(): void
    {
        $baseCart = $this->objectManager->get(GetQuoteByReservedOrderId::class)->execute('test_order_1');
        $this->objectManager->get(PaymentTokenForQuote::class)->execute($baseCart);

        $order = $this->objectManager->create(ConvertComponentsPaymentToOrder::class)->execute(
            $baseCart,
            $this->objectManager->get(ExpressPaymentBuilder::class)->build('tr_expressretry'),
        );

        $this->assertNotEquals((int)$baseCart->getId(), (int)$order->getQuoteId());
        $this->assertEquals(
            (int)$order->getEntityId(),
            (int)$this->objectManager->create(GetOrderByTransactionId::class)
                ->execute('tr_expressretry')
                ->getEntityId(),
        );
    }

    /**
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_and_shipping_method_saved.php
     */
    public function testReturnsNullWhenNoOrderCarriesTheTransactionId(): void
    {
        $this->assertNull(
            $this->objectManager->create(GetOrderByTransactionId::class)->execute('tr_doesnotexist'),
        );
    }
}
