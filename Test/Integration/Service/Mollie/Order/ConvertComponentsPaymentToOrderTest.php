<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie\Order;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\TestFramework\Quote\Model\GetQuoteByReservedOrderId;
use Mollie\Api\Resources\Payment;
use Mollie\Payment\Api\PaymentTokenRepositoryInterface;
use Mollie\Payment\Service\Mollie\Order\ConvertComponentsPaymentToOrder;
use Mollie\Payment\Service\PaymentToken\PaymentTokenForQuote;
use Mollie\Payment\Test\Integration\ExpressPaymentBuilder;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class ConvertComponentsPaymentToOrderTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_and_shipping_method_saved.php
     */
    public function testConnectsThePlacedOrderToThePaymentTokenOfTheBaseCart(): void
    {
        $baseCart = $this->loadQuote();
        $order = $this->convert($baseCart, $this->buildPayment('tr_expresstoken'));

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
        $order = $this->convert($this->loadQuote(), $this->buildPayment('tr_expresstransaction'));

        $this->assertEquals(
            'tr_expresstransaction',
            $this->objectManager->get(OrderRepositoryInterface::class)
                ->get((int)$order->getEntityId())
                ->getMollieTransactionId(),
        );
    }

    /**
     * A wallet address without a second street line must not reach the database as an array.
     *
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_and_shipping_method_saved.php
     */
    public function testStoresASingleLineStreetAsAString(): void
    {
        $order = $this->convert($this->loadQuote(), $this->buildPayment('tr_expressstreet'));

        $this->assertEquals(['Keizersgracht 126'], $order->getShippingAddress()->getStreet());
    }

    /**
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_and_shipping_method_saved.php
     */
    public function testKeepsBothStreetLinesWhenTheWalletSendsThem(): void
    {
        $payment = $this->objectManager->get(ExpressPaymentBuilder::class)->build('tr_expressstreets', 'Unit 4');
        $order = $this->convert($this->loadQuote(), $payment);

        $this->assertEquals(['Keizersgracht 126', 'Unit 4'], $order->getShippingAddress()->getStreet());
    }

    /**
     * With requiredCustomerDetails "email" Mollie returns a billingAddress that only holds the email.
     *
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_and_shipping_method_saved.php
     */
    public function testPlacesTheOrderWhenMollieOnlyCollectedTheEmail(): void
    {
        $payment = $this->buildPayment('tr_expressemailonly');
        $payment->billingAddress = new \stdClass();
        $payment->billingAddress->email = 'guest@example.com';
        $payment->shippingAddress = null;

        $order = $this->convert($this->loadQuote(), $payment);

        $this->assertSame('guest@example.com', $order->getCustomerEmail());
        $this->assertNotEmpty($order->getShippingAddress()->getStreet(), 'The address must come from the cart');
    }

    /**
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_and_shipping_method_saved.php
     */
    public function testUsesThePhoneOfTheCartWhenMollieSendsNone(): void
    {
        $baseCart = $this->loadQuote();
        $payment = $this->buildPayment('tr_expresscartphone');
        unset($payment->billingAddress->phone, $payment->shippingAddress->phone);

        $order = $this->convert($baseCart, $payment);

        $this->assertSame($baseCart->getShippingAddress()->getTelephone(), $order->getShippingAddress()->getTelephone());
    }

    /**
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_and_shipping_method_saved.php
     * @magentoConfigFixture current_store customer/address/telephone_show req
     */
    public function testUsesAPlaceholderPhoneWhenNeitherMollieNorTheCartHasOne(): void
    {
        $baseCart = $this->loadQuote();
        $baseCart->getShippingAddress()->setTelephone(null);
        $baseCart->getBillingAddress()->setTelephone(null);
        $payment = $this->buildPayment('tr_expressnophone');
        unset($payment->billingAddress->phone, $payment->shippingAddress->phone);

        $order = $this->convert($baseCart, $payment);

        $this->assertSame('-', $order->getShippingAddress()->getTelephone());
        $this->assertSame('-', $order->getBillingAddress()->getTelephone());
    }

    private function convert(CartInterface $baseCart, Payment $payment): OrderInterface
    {
        $this->objectManager->get(PaymentTokenForQuote::class)->execute($baseCart);

        return $this->objectManager->create(ConvertComponentsPaymentToOrder::class)->execute($baseCart, $payment);
    }

    private function buildPayment(string $transactionId): Payment
    {
        return $this->objectManager->get(ExpressPaymentBuilder::class)->build($transactionId);
    }

    private function loadQuote(): CartInterface
    {
        return $this->objectManager->get(GetQuoteByReservedOrderId::class)->execute('test_order_1');
    }
}
