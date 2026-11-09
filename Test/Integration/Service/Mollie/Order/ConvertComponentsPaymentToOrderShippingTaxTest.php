<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie\Order;

use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Tax\Api\Data\TaxRateInterfaceFactory;
use Magento\Tax\Api\Data\TaxRuleInterfaceFactory;
use Magento\Tax\Api\TaxRateRepositoryInterface;
use Magento\Tax\Api\TaxRuleRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Quote\Model\GetQuoteByReservedOrderId;
use Mollie\Api\Resources\Payment;
use Mollie\Payment\Service\Mollie\Order\ConvertComponentsPaymentToOrder;
use Mollie\Payment\Service\PaymentToken\PaymentTokenForQuote;
use Mollie\Payment\Test\Integration\ExpressPaymentBuilder;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

/**
 * @magentoAppIsolation enabled
 * @see https://github.com/mollie/magento2/issues/1129
 */
class ConvertComponentsPaymentToOrderShippingTaxTest extends IntegrationTestCase
{
    private const TAXABLE_GOODS_CLASS_ID = 2;
    private const RETAIL_CUSTOMER_CLASS_ID = 3;
    private const TAX_RATE_PERCENTAGE = 20;
    private const SHIPPING_EXCL_TAX = 7.95;
    private const SHIPPING_TAX = 1.59;
    private const SHIPPING_INCL_TAX = '9.54';
    private const AMOUNT_DELTA = 0.001;

    public static function createNetherlandsShippingTaxRule(): void
    {
        $objectManager = Bootstrap::getObjectManager();

        $rate = $objectManager->get(TaxRateInterfaceFactory::class)->create();
        $rate->setCode('NL shipping ' . self::TAX_RATE_PERCENTAGE);
        $rate->setTaxCountryId('NL');
        $rate->setTaxPostcode('*');
        $rate->setRate(self::TAX_RATE_PERCENTAGE);
        $savedRate = $objectManager->get(TaxRateRepositoryInterface::class)->save($rate);

        $rule = $objectManager->get(TaxRuleInterfaceFactory::class)->create();
        $rule->setCode('NL shipping rule');
        $rule->setPriority(0);
        $rule->setPosition(0);
        $rule->setCustomerTaxClassIds([self::RETAIL_CUSTOMER_CLASS_ID]);
        $rule->setProductTaxClassIds([self::TAXABLE_GOODS_CLASS_ID]);
        $rule->setTaxRateIds([$savedRate->getId()]);
        $objectManager->get(TaxRuleRepositoryInterface::class)->save($rule);
    }

    /**
     * @magentoDataFixture createNetherlandsShippingTaxRule
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_and_shipping_method_saved.php
     * @magentoConfigFixture current_store tax/classes/shipping_tax_class 2
     * @magentoConfigFixture current_store shipping/origin/country_id NL
     * @magentoConfigFixture current_store tax/defaults/country NL
     * @magentoConfigFixture current_store tax/calculation/shipping_includes_tax 0
     * @magentoConfigFixture current_store carriers/flatrate/type O
     * @magentoConfigFixture current_store carriers/flatrate/price 7.95
     */
    public function testDoesNotAddTaxAgainWhenShippingPricesAreConfiguredExcludingTax(): void
    {
        $baseCart = $this->givenADutchCart();

        $order = $this->convert($baseCart, $this->buildPaymentWithShippingVat());

        $this->assertOrderMatchesWhatTheCustomerPaid($baseCart, $order);
    }

    /**
     * @magentoDataFixture createNetherlandsShippingTaxRule
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_and_shipping_method_saved.php
     * @magentoConfigFixture current_store tax/classes/shipping_tax_class 2
     * @magentoConfigFixture current_store shipping/origin/country_id NL
     * @magentoConfigFixture current_store tax/defaults/country NL
     * @magentoConfigFixture current_store tax/calculation/shipping_includes_tax 0
     * @magentoConfigFixture current_store carriers/flatrate/type O
     * @magentoConfigFixture current_store carriers/flatrate/price 7.95
     */
    public function testDoesNotAddTaxAgainWhenThePaymentHasNoVatOnTheShippingLine(): void
    {
        $baseCart = $this->givenADutchCart();

        $order = $this->convert($baseCart, $this->buildPaymentWithoutShippingVat());

        $this->assertOrderMatchesWhatTheCustomerPaid($baseCart, $order);
    }

    /**
     * @magentoDataFixture createNetherlandsShippingTaxRule
     * @magentoDataFixture Magento/Checkout/_files/quote_with_address_and_shipping_method_saved.php
     * @magentoConfigFixture current_store tax/classes/shipping_tax_class 2
     * @magentoConfigFixture current_store shipping/origin/country_id NL
     * @magentoConfigFixture current_store tax/defaults/country NL
     * @magentoConfigFixture current_store tax/calculation/shipping_includes_tax 1
     * @magentoConfigFixture current_store carriers/flatrate/type O
     * @magentoConfigFixture current_store carriers/flatrate/price 9.54
     */
    public function testKeepsTheShippingAmountWhenShippingPricesAreConfiguredIncludingTax(): void
    {
        $baseCart = $this->givenADutchCart();

        $order = $this->convert($baseCart, $this->buildPaymentWithShippingVat());

        $this->assertOrderMatchesWhatTheCustomerPaid($baseCart, $order);
    }

    private function assertOrderMatchesWhatTheCustomerPaid(CartInterface $baseCart, OrderInterface $order): void
    {
        $this->assertEqualsWithDelta(
            (float)self::SHIPPING_INCL_TAX,
            (float)$order->getShippingInclTax(),
            self::AMOUNT_DELTA,
        );
        $this->assertEqualsWithDelta(self::SHIPPING_EXCL_TAX, (float)$order->getShippingAmount(), self::AMOUNT_DELTA);
        $this->assertEqualsWithDelta(self::SHIPPING_TAX, (float)$order->getShippingTaxAmount(), self::AMOUNT_DELTA);
        $this->assertEqualsWithDelta(
            (float)$baseCart->getGrandTotal(),
            (float)$order->getGrandTotal(),
            self::AMOUNT_DELTA,
            'Mollie charged the grand total of the cart, a different order total leaves the order in pending payment',
        );
    }

    private function givenADutchCart(): CartInterface
    {
        $cart = $this->objectManager->get(GetQuoteByReservedOrderId::class)->execute('test_order_1');
        $cart->getShippingAddress()
            ->setCountryId('NL')
            ->setRegionId(null)
            ->setRegion(null)
            ->setPostcode('1015 CW')
            ->setCollectShippingRates(true);
        $cart->collectTotals();
        $this->objectManager->get(CartRepositoryInterface::class)->save($cart);

        $this->assertEqualsWithDelta(
            (float)self::SHIPPING_INCL_TAX,
            (float)$cart->getShippingAddress()->getShippingInclTax(),
            self::AMOUNT_DELTA,
            'The base cart must have the shipping that Mollie charged for this test to be useful',
        );

        return $cart;
    }

    private function buildPaymentWithShippingVat(): Payment
    {
        $builder = $this->objectManager->get(ExpressPaymentBuilder::class);
        $payment = $builder->build('tr_expressshippingvat');
        $payment->lines = [
            $builder->productLine(),
            $builder->shippingLineWithVat(
                self::SHIPPING_INCL_TAX,
                sprintf('%.2f', self::TAX_RATE_PERCENTAGE),
                sprintf('%.2f', self::SHIPPING_TAX),
            ),
        ];

        return $payment;
    }

    private function buildPaymentWithoutShippingVat(): Payment
    {
        $builder = $this->objectManager->get(ExpressPaymentBuilder::class);
        $payment = $builder->build('tr_expressshippingnovat');
        $payment->lines = [$builder->productLine(), $builder->shippingLine(self::SHIPPING_INCL_TAX)];

        return $payment;
    }

    private function convert(CartInterface $baseCart, Payment $payment): OrderInterface
    {
        $this->objectManager->get(PaymentTokenForQuote::class)->execute($baseCart);

        return $this->objectManager->create(ConvertComponentsPaymentToOrder::class)->execute($baseCart, $payment);
    }
}
