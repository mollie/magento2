<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Controller\Express;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\TestFramework\Quote\Model\GetQuoteByReservedOrderId;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Payment;
use Mollie\Payment\Controller\Express\Webhook;
use Mollie\Payment\Service\PaymentToken\PaymentTokenForQuote;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use stdClass;

class WebhookTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_and_shipping_method_saved.php
     */
    public function testReturnsTheExistingOrderWhenTheSamePaymentIsReceivedAgain(): void
    {
        $baseCart = $this->loadQuote();
        $this->objectManager->get(PaymentTokenForQuote::class)->execute($baseCart);

        $payment = $this->buildPayment('tr_expressretry');
        $webhook = $this->objectManager->create(Webhook::class);

        $order = $webhook->placeOrRetrieveOrder($baseCart, $payment);
        $retry = $webhook->placeOrRetrieveOrder($baseCart, $payment);

        $this->assertEquals($order->getEntityId(), $retry->getEntityId());
        $this->assertCount(
            1,
            $this->ordersForTransaction('tr_expressretry'),
            'A retried webhook must not place a second order for the same payment',
        );
    }

    /**
     * @return \Magento\Sales\Api\Data\OrderInterface[]
     */
    private function ordersForTransaction(string $transactionId): array
    {
        $searchCriteria = $this->objectManager->create(SearchCriteriaBuilder::class)
            ->addFilter('mollie_transaction_id', $transactionId)
            ->create();

        return $this->objectManager->get(OrderRepositoryInterface::class)->getList($searchCriteria)->getItems();
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
