<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie\Order;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\TestFramework\Quote\Model\GetQuoteByReservedOrderId;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Payment;
use Mollie\Payment\Api\PaymentTokenRepositoryInterface;
use Mollie\Payment\Service\Mollie\Order\ConvertComponentsPaymentToOrder;
use Mollie\Payment\Service\PaymentToken\PaymentTokenForQuote;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use stdClass;

class ConvertComponentsPaymentToOrderTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_and_shipping_method_saved.php
     */
    public function testConnectsThePlacedOrderToThePaymentTokenOfTheBaseCart(): void
    {
        $baseCart = $this->loadQuote();
        $this->objectManager->get(PaymentTokenForQuote::class)->execute($baseCart);

        $order = $this->objectManager->create(ConvertComponentsPaymentToOrder::class)
            ->execute($baseCart, $this->buildPayment('tr_expresstoken'));

        $tokens = $this->objectManager->get(PaymentTokenRepositoryInterface::class)
            ->getByCart($baseCart)
            ->getItems();

        $this->assertCount(1, $tokens);
        $this->assertEquals(
            (int)$order->getEntityId(),
            (int)array_shift($tokens)->getOrderId(),
            'Without this link the customer is sent back to the cart with an error while the payment succeeded',
        );
    }

    /**
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_and_shipping_method_saved.php
     */
    public function testStoresTheTransactionIdOnTheOrderSoTheWebhookCanFindItBack(): void
    {
        $baseCart = $this->loadQuote();
        $this->objectManager->get(PaymentTokenForQuote::class)->execute($baseCart);

        $order = $this->objectManager->create(ConvertComponentsPaymentToOrder::class)
            ->execute($baseCart, $this->buildPayment('tr_expresstransaction'));

        $this->assertEquals(
            'tr_expresstransaction',
            $this->objectManager->get(OrderRepositoryInterface::class)
                ->get((int)$order->getEntityId())
                ->getMollieTransactionId(),
        );
    }

    private function loadQuote(): CartInterface
    {
        return $this->objectManager->get(GetQuoteByReservedOrderId::class)->execute('test_order_1');
    }

    private function buildPayment(string $transactionId): Payment
    {
        $payment = new Payment(new MollieApiClient());
        $payment->id = $transactionId;
        $payment->method = 'applepay';
        $payment->lines = [$this->productLine(), $this->shippingLine('5.00')];
        $payment->billingAddress = $this->address();
        $payment->shippingAddress = $this->address();
        $payment->_links = new stdClass();

        return $payment;
    }

    private function address(): stdClass
    {
        $address = new stdClass();
        $address->givenName = 'John';
        $address->familyName = 'Doe';
        $address->streetAndNumber = 'Keizersgracht 126';
        $address->postalCode = '1015 CW';
        $address->city = 'Amsterdam';
        $address->country = 'NL';
        $address->phone = '0612345678';
        $address->email = 'aaa@aaa.com';

        return $address;
    }

    private function productLine(): stdClass
    {
        $line = new stdClass();
        $line->type = 'physical';
        $line->description = '[simple] Simple Product';
        $line->quantity = 1;
        $line->unitPrice = new stdClass();
        $line->unitPrice->value = '10.00';
        $line->totalAmount = new stdClass();
        $line->totalAmount->value = '10.00';

        return $line;
    }

    private function shippingLine(string $value): stdClass
    {
        $line = new stdClass();
        $line->type = 'shipping_fee';
        $line->description = 'Standard delivery';
        $line->quantity = 1;
        $line->unitPrice = new stdClass();
        $line->unitPrice->value = $value;
        $line->totalAmount = new stdClass();
        $line->totalAmount->value = $value;

        return $line;
    }
}
