<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\PaymentFee\Types;

use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Mollie\Payment\Service\PaymentFee\Types\Percentage;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class PercentageTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/payment_surcharge_percentage 2
     *
     * @return void
     */
    public function testUsesBasePrices(): void
    {
        /** @var Percentage $instance */
        $instance = $this->objectManager->create(Percentage::class);

        $cart = $this->objectManager->create(Quote::class);
        $cart->load('test01', 'reserved_order_id');
        $cart->getPayment()->setMethod('mollie_methods_ideal');

        /** @var Total $total */
        $total = $this->objectManager->create(Total::class);

        $total->setBaseShippingInclTax(10);
        $total->setData('base_subtotal_incl_tax', 10);

        $total->setShippingInclTax(999); // Intentionally set to a high value to make sure it's not used
        $total->setData('subtotal_incl_tax', 999);

        $result = $instance->calculate($cart, $total);

        $this->assertEquals(0.2, $result->getAmount());
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/payment_surcharge_percentage 2
     * @magentoConfigFixture default_store payment/mollie_general/include_discount_in_surcharge 1
     *
     * @return void
     */
    public function testUsesDiscountForSurcharge(): void
    {
        /** @var Percentage $instance */
        $instance = $this->objectManager->create(Percentage::class);

        $cart = $this->objectManager->create(Quote::class);
        $cart->load('test01', 'reserved_order_id');
        $cart->getPayment()->setMethod('mollie_methods_ideal');

        /** @var Total $total */
        $total = $this->objectManager->create(Total::class);

        $total->setData('base_subtotal_incl_tax', 100);
        $total->setBaseDiscountAmount(10);

        $result = $instance->calculate($cart, $total);

        // 100 - 10 = 90. 2% of 90 = 1.8
        $this->assertEquals(1.8, $result->getAmount());
    }
}
