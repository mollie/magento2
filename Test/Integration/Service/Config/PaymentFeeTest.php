<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Config;

use Magento\Checkout\Model\Session;
use Magento\Quote\Model\Quote;
use Magento\Tax\Api\TaxCalculationInterface;
use Magento\Tax\Api\TaxRateRepositoryInterface;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class PaymentFeeTest extends IntegrationTestCase
{
    public function returnsTheCorrectIncludingTaxNumberProvider()
    {
        return [
            ['klarnapaylater', 1.23],
            ['klarnasliceit', 1.95],
        ];
    }

    /**
     * @magentoDataFixture Magento/Checkout/_files/quote_with_payment_saved.php
     * @magentoConfigFixture current_store payment/mollie_methods_klarnapaylater/payment_surcharge 1,23
     * @magentoConfigFixture current_store payment/mollie_methods_klarnasliceit/payment_surcharge 1,95
     *
     * @dataProvider returnsTheCorrectIncludingTaxNumberProvider
     */
    public function testReturnsTheCorrectIncludingTaxNumber($method, $amount)
    {
        /** @var PaymentFee $instance */
        $instance = $this->objectManager->create(PaymentFee::class);

        /** @var $session \Magento\Checkout\Model\Session  */
        $session = $this->objectManager->create(Session::class);
        $quote = $session->getQuote();
        $quote->getPayment()->setMethod('mollie_methods_' . $method);

        $result = $instance->includingTax($quote);

        $this->assertEquals($amount, $result);
    }

    /**
     * @magentoDataFixture Magento/Checkout/_files/quote_with_payment_saved.php
     * @magentoConfigFixture current_store payment/mollie_methods_klarnasliceit/payment_surcharge 1,95
     * @magentoConfigFixture current_store payment/mollie_methods_klarnasliceit/payment_surcharge_tax_class 2
     */
    public function testReturnsTheCorrectExcludingTaxAmount()
    {
        /** @var $session \Magento\Checkout\Model\Session  */
        $session = $this->objectManager->create(Session::class);
        $quote = $session->getQuote();

        /** @var \Magento\Tax\Api\Data\TaxRateInterface $rate */
        $rate = $this->objectManager->create(\Magento\Tax\Api\Data\TaxRateInterface::class);
        $rate->setTaxCountryId('US');
        $rate->setTaxRegionId(0);
        $rate->setTaxPostcode('*');
        $rate->setCode('testshipping_testshipping');
        $rate->setRate(21);

        $this->objectManager->get(TaxRateRepositoryInterface::class)->save($rate);

        /** @var \Magento\Tax\Model\ResourceModel\Calculation $taxCalculation */
        $taxCalculation = $this->objectManager->create(\Magento\Tax\Model\ResourceModel\Calculation::class);
        $taxCalculation->getConnection()->insert($taxCalculation->getMainTable(), [
            'tax_calculation_rate_id' => $rate->getId(),
            'tax_calculation_rule_id' => 1,
            'customer_tax_class_id' => $quote->getCustomerTaxClassId(),
            'product_tax_class_id' => 2,
        ]);

        /** @var PaymentFee $instance */
        $instance = $this->objectManager->create(PaymentFee::class);

        $quote->getPayment()->setMethod('mollie_methods_klarnasliceit');

        $result = $instance->excludingTax($quote);

        $this->assertEquals(1.6115702479339, $result);
    }

    /**
     * @magentoDataFixture Magento/Checkout/_files/quote_with_payment_saved.php
     */
    public function testReturnsZeroIfNotAValidPaymentMethod()
    {
        /** @var PaymentFee $instance */
        $instance = $this->objectManager->create(PaymentFee::class);

        /** @var $session \Magento\Checkout\Model\Session  */
        $session = $this->objectManager->create(Session::class);
        $quote = $session->getQuote();
        $quote->getPayment()->setMethod('not_relevant_payment_method');

        $result = $instance->includingTax($quote);

        $this->assertSame(0.0, $result);
    }

    /**
     * @magentoDataFixture Magento/Checkout/_files/quote_with_payment_saved.php
     */
    public function isAvailableForMethodProvider()
    {
        return [
            ['mollie_methods_klarnapaylater', true],
            ['mollie_methods_klarnasliceit', true],
            ['not_relevant_payment_method', false],
        ];
    }

    /**
     * @dataProvider isAvailableForMethodProvider
     */
    public function testIsAvailableForMethod($method, $expected)
    {
        /** @var PaymentFee $instance */
        $instance = $this->objectManager->create(PaymentFee::class);

        /** @var $session \Magento\Checkout\Model\Session  */
        $session = $this->objectManager->create(Session::class);
        $quote = $session->getQuote();
        $quote->getPayment()->setMethod($method);

        $result = $instance->isAvailableForMethod($quote);

        $this->assertSame($expected, $result);
    }
}
