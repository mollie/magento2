<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Service\Config;

use Magento\Checkout\Model\Session;
use Magento\Quote\Model\Quote;
use Magento\Tax\Api\TaxCalculationInterface;
use Magento\Tax\Api\TaxRateRepositoryInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Adminhtml\Source\PaymentFeeType;
use Mollie\Payment\Service\Config\PaymentFee;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class PaymentFeeTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Checkout/_files/quote_with_payment_saved.php
     */
    public function isAvailableForMethodProvider()
    {
        return [
            ['mollie_methods_applepay', true],
            ['mollie_methods_alma', true],
            ['mollie_methods_bancomatpay', true],
            ['mollie_methods_bancontact', true],
            ['mollie_methods_banktransfer', true],
            ['mollie_methods_belfius', true],
            ['mollie_methods_billie', true],
            ['mollie_methods_blik', true],
            ['mollie_methods_creditcard', true],
            ['mollie_methods_directdebit', true],
            ['mollie_methods_eps', true],
            ['mollie_methods_giftcard', true],
            ['mollie_methods_googlepay', true],
            ['mollie_methods_ideal', true],
            ['mollie_methods_in3', true],
            ['mollie_methods_kbc', true],
            ['mollie_methods_klarna', true],
            ['mollie_methods_klarnapaylater', true],
            ['mollie_methods_klarnapaynow', true],
            ['mollie_methods_klarnasliceit', true],
            ['mollie_methods_mybank', true],
            ['mollie_methods_paypal', true],
            ['mollie_methods_paysafecard', true],
            ['mollie_methods_pointofsale', true],
            ['mollie_methods_payconiq', true],
            ['mollie_methods_przelewy24', true],
            ['mollie_methods_riverty', true],
            ['mollie_methods_sofort', true],
            ['mollie_methods_trustly', true],
            ['mollie_methods_twint', true],
            ['mollie_methods_voucher', true],
            ['not_relevant_payment_method', false],
        ];
    }

    /**
     * @dataProvider isAvailableForMethodProvider
     */
    public function testIsAvailableForMethod($method, $expected)
    {
        $configMock = $this->createMock(Config::class);
        $configMock->method('paymentSurchargeType')->willReturn(PaymentFeeType::PERCENTAGE);

        /** @var PaymentFee $instance */
        $instance = $this->objectManager->create(PaymentFee::class, [
            'config' => $configMock,
        ]);

        /** @var $session \Magento\Checkout\Model\Session  */
        $session = $this->objectManager->create(Session::class);
        $quote = $session->getQuote();
        $quote->getPayment()->setMethod($method);

        $result = $instance->isAvailableForMethod($quote);

        $this->assertSame($expected, $result);
    }
}
