<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie\Express;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\TestFramework\Quote\Model\GetQuoteByReservedOrderId;
use Mollie\Payment\Api\PaymentTokenRepositoryInterface;
use Mollie\Payment\Service\Mollie\Express\ProductBuyRequest;
use Mollie\Payment\Service\Mollie\Express\ProductPaymentToken;
use Mollie\Payment\Service\Mollie\Express\ProductQuote;
use Mollie\Payment\Service\Mollie\Order\ConvertComponentsPaymentToOrder;
use Mollie\Payment\Service\PaymentToken\PaymentTokenForQuote;
use Mollie\Payment\Test\Integration\ExpressPaymentBuilder;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

/**
 * @magentoAppArea frontend
 */
class ProductPaymentTokenTest extends IntegrationTestCase
{
    private const TOKEN = 'producttoken0000000000000000000a';

    /**
     * The redirect URL of a product page session contains a token that only gets a row once the order exists.
     *
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_and_shipping_method_saved.php
     */
    public function testLinksTheTokenOfTheSessionToTheOrder(): void
    {
        $order = $this->placeProductPageOrder('tr_producttoken');

        $this->objectManager->create(ProductPaymentToken::class)->link($order, self::TOKEN);

        $token = $this->objectManager->get(PaymentTokenRepositoryInterface::class)->getByToken(self::TOKEN);
        $this->assertNotNull($token);
        $this->assertEquals($order->getEntityId(), $token->getOrderId());
        $this->assertEquals($order->getQuoteId(), $token->getCartId());
    }

    /**
     * Mollie retries the webhook, so linking twice must not fail on the unique token.
     *
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_and_shipping_method_saved.php
     */
    public function testLinkingTheSameTokenTwiceKeepsOneToken(): void
    {
        $order = $this->placeProductPageOrder('tr_producttokentwice');
        $paymentToken = $this->objectManager->create(ProductPaymentToken::class);

        $paymentToken->link($order, self::TOKEN);
        $paymentToken->link($order, self::TOKEN);

        $this->assertEquals(
            $order->getEntityId(),
            $this->objectManager->get(PaymentTokenRepositoryInterface::class)->getByToken(self::TOKEN)->getOrderId(),
        );
    }

    /**
     * The product page quote has no id. Looking up its tokens by cart must not hand out the tokens of other carts.
     *
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_and_shipping_method_saved.php
     */
    public function testPlacingTheOrderDoesNotLinkThePaymentTokensOfOtherCarts(): void
    {
        $otherCart = $this->objectManager->get(GetQuoteByReservedOrderId::class)->execute('test_order_1');
        $otherToken = $this->objectManager->get(PaymentTokenForQuote::class)->execute($otherCart);

        $order = $this->placeProductPageOrder('tr_productothertokens');

        $this->assertSame('simple', $order->getItems()[array_key_first($order->getItems())]->getSku());
        $this->assertNull(
            $this->objectManager->get(PaymentTokenRepositoryInterface::class)->getByToken($otherToken)->getOrderId(),
        );
    }

    private function placeProductPageOrder(string $transactionId): OrderInterface
    {
        $quote = $this->objectManager->create(ProductQuote::class)
            ->create(ProductBuyRequest::fromRequestParams(['product' => '1', 'qty' => '1']), 1, null);
        $payment = $this->objectManager->get(ExpressPaymentBuilder::class)->build($transactionId);
        $payment->billingAddress->email = $transactionId . '@example.com';
        $payment->shippingAddress->email = $transactionId . '@example.com';

        return $this->objectManager->create(ConvertComponentsPaymentToOrder::class)->execute($quote, $payment);
    }
}
