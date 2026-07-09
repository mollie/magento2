<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie\Order\ConvertComponentsPaymentToOrder;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartInterfaceFactory;
use Magento\TestFramework\Quote\Model\GetQuoteByReservedOrderId;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Payment;
use Mollie\Payment\Service\Mollie\Order\ConvertComponentsPaymentToOrder\SetShippingOnCart;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use stdClass;

class SetShippingOnCartTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Checkout/_files/quote_with_simple_product_and_custom_option.php
     */
    public function testItAddsCustomOptionProductsFromTheBaseCartWithoutLookingUpTheCompositeSku(): void
    {
        $baseCart = $this->objectManager->create(Session::class)->getQuote();
        $baseItem = array_values($baseCart->getAllVisibleItems())[0];

        $this->assertStringContainsString(
            '-',
            $baseItem->getSku(),
            'The custom option should turn the cart item SKU into a composite that is not a real product SKU',
        );
        $this->assertCompositeSkuCannotBeResolvedByProductRepository($baseItem->getSku());

        $cart = $this->createEmptyCart($baseCart);
        $payment = $this->buildPayment('ideal', [
            $this->productLine($baseItem->getSku(), (string) $baseItem->getName()),
            $this->shippingLine('5.00'),
        ]);

        $instance = $this->objectManager->create(SetShippingOnCart::class);
        $instance->execute($baseCart, $cart, $payment);

        $items = array_values($cart->getAllVisibleItems());
        $this->assertCount(1, $items);
        $this->assertEquals($baseItem->getProductId(), $items[0]->getProductId());
        $this->assertNotEmpty(
            $items[0]->getBuyRequest()->getOptions(),
            'The selected custom options should be preserved on the new cart',
        );
        $this->assertEquals(
            $baseItem->getBuyRequest()->getOptions(),
            $items[0]->getBuyRequest()->getOptions(),
            'The exact custom option selection should be carried over to the new cart',
        );
    }

    /**
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_and_shipping_method_saved.php
     */
    public function testZeroesShippingForWalletPaymentWithoutShippingLine(): void
    {
        $cart = $this->loadQuote();
        $payment = $this->buildPayment('applepay', [$this->productLine()]);

        /** @var SetShippingOnCart $instance */
        $instance = $this->objectManager->create(SetShippingOnCart::class);
        $instance->execute($this->createEmptyCart($cart), $cart, $payment);

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
        $instance->execute($this->createEmptyCart($cart), $cart, $payment);

        $rates = $cart->getShippingAddress()->getShippingRatesCollection()->getItems();

        $this->assertEquals('flatrate_flatrate', $cart->getShippingAddress()->getShippingMethod());
        $this->assertCount(1, $rates);
        $this->assertEquals(7.50, (float)reset($rates)->getPrice());
    }

    private function loadQuote(): CartInterface
    {
        return $this->objectManager->get(GetQuoteByReservedOrderId::class)->execute('test_order_1');
    }

    private function createEmptyCart(CartInterface $baseCart): CartInterface
    {
        /** @var CartInterface $cart */
        $cart = $this->objectManager->create(CartInterfaceFactory::class)->create();
        $cart->setStoreId($baseCart->getStoreId());

        return $cart;
    }

    private function buildPayment(string $method, array $lines): Payment
    {
        $payment = new Payment(new MollieApiClient());
        $payment->method = $method;
        $payment->lines = $lines;

        return $payment;
    }

    private function productLine(string $sku = 'simple', string $name = 'Simple Product'): stdClass
    {
        $line = new stdClass();
        $line->type = 'physical';
        $line->description = '[' . $sku . '] ' . $name;
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

    private function assertCompositeSkuCannotBeResolvedByProductRepository(string $sku): void
    {
        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);

        try {
            $productRepository->get($sku);
            $this->fail('Expected the composite SKU to not resolve to a real product: ' . $sku);
        } catch (NoSuchEntityException $exception) {
            $this->assertTrue(true);
        }
    }
}
