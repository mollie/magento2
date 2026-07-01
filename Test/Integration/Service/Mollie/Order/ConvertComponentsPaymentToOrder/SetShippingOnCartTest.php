<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie\Order\ConvertComponentsPaymentToOrder;

use Magento\Quote\Api\Data\CartInterface;
use Magento\TestFramework\Quote\Model\GetQuoteByReservedOrderId;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Payment;
use Mollie\Payment\Service\Mollie\Order\ConvertComponentsPaymentToOrder\SetShippingOnCart;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use stdClass;

class SetShippingOnCartTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_and_shipping_method_saved.php
     */
    public function testZeroesShippingForWalletPaymentWithoutShippingLine(): void
    {
        $cart = $this->loadQuote();
        $payment = $this->buildPayment('applepay', [$this->productLine()]);

        /** @var SetShippingOnCart $instance */
        $instance = $this->objectManager->create(SetShippingOnCart::class);
        $instance->execute($cart, $payment);

        $rates = $cart->getShippingAddress()->getShippingRatesCollection()->getItems();

        $this->assertNotEmpty($rates);
        foreach ($rates as $rate) {
            $this->assertEquals(0.0, (float)$rate->getPrice());
        }
    }

    /**
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_and_shipping_method_saved.php
     */
    public function testAppliesShippingWhenWalletPaymentContainsShippingLine(): void
    {
        $cart = $this->loadQuote();
        $payment = $this->buildPayment('applepay', [$this->productLine(), $this->shippingLine('7.50')]);

        /** @var SetShippingOnCart $instance */
        $instance = $this->objectManager->create(SetShippingOnCart::class);
        $instance->execute($cart, $payment);

        $rates = $cart->getShippingAddress()->getShippingRatesCollection()->getItems();

        $this->assertEquals('flatrate_flatrate', $cart->getShippingAddress()->getShippingMethod());
        $this->assertCount(1, $rates);
        $this->assertEquals(7.50, (float)reset($rates)->getPrice());
    }

    private function loadQuote(): CartInterface
    {
        return $this->objectManager->get(GetQuoteByReservedOrderId::class)->execute('test_order_1');
    }

    private function buildPayment(string $method, array $lines): Payment
    {
        $payment = new Payment(new MollieApiClient());
        $payment->method = $method;
        $payment->lines = $lines;

        return $payment;
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
