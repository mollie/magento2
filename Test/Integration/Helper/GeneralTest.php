<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Helper;

use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Locale\Resolver;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Mollie\Payment\Helper\General;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class GeneralTest extends IntegrationTestCase
{
    /**
     * @magentoConfigFixture default_store payment/mollie_general/locale en_US
     */
    public function testGetLocaleCodeWithFixedLocale()
    {
        /** @var General $instance */
        $instance = $this->objectManager->get(General::class);

        $result = $instance->getLocaleCode(null, 'order');

        $this->assertEquals('en_US', $result);
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_general/locale
     */
    public function testGetLocaleCodeWithAutomaticDetectionAndAValidLocale()
    {
        /** @var Resolver $localeResolver */
        $localeResolver = $this->objectManager->get(Resolver::class);
        $localeResolver->setLocale('en_US');

        /** @var General $instance */
        $instance = $this->objectManager->get(General::class);

        $result = $instance->getLocaleCode(null, 'order');

        $this->assertEquals('en_US', $result);
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_general/locale
     */
    public function testGetLocaleCodeWithAutomaticDetectionAndAInvalidLocale()
    {
        /** @var Resolver $localeResolver */
        $localeResolver = $this->objectManager->get(Resolver::class);
        $localeResolver->setLocale('en_GB');

        /** @var General $instance */
        $instance = $this->objectManager->get(General::class);

        $result = $instance->getLocaleCode(null, 'order');

        $this->assertEquals('en_US', $result);
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_general/locale store
     */
    public function testGetLocaleCodeBasedOnTheStoreLocaleWithAValidValue()
    {
        /** @var Resolver $localeResolver */
        $localeResolver = $this->objectManager->get(Resolver::class);
        $localeResolver->setLocale('en_GB');

        /** @var General $instance */
        $instance = $this->objectManager->get(General::class);

        $result = $instance->getLocaleCode(null, 'order');

        $this->assertEquals('en_US', $result);
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_general/locale
     */
    public function testGetLocaleCanReturnNull()
    {
        /** @var Resolver $localeResolver */
        $localeResolver = $this->objectManager->get(Resolver::class);
        $localeResolver->setLocale('en_GB');

        /** @var General $instance */
        $instance = $this->objectManager->get(General::class);

        $result = $instance->getLocaleCode(null, 'payment');

        $this->assertNull($result);
    }

    public function testIsPaidUsingMollieOrdersApiCatchesExceptions()
    {
        $order = $this->objectManager->create(OrderInterface::class);

        $payment = $this->objectManager->create(OrderPaymentInterface::class);
        $payment->setMethod('non-existing-method');
        $order->setPayment($payment);

        /** @var General $instance */
        $instance = $this->objectManager->create(General::class);
        $result = $instance->isPaidUsingMollieOrdersApi($order);

        $this->assertFalse($result);
    }

    /**
     * @magentoDataFixture Magento/Store/_files/second_store.php
     * @magentoConfigFixture current_store payment/mollie_general/apikey_test keyA
     * @magentoConfigFixture fixture_second_store_store payment/mollie_general/apikey_test keyB
     */
    public function testGetApiKeyGivesAUniqueKeyPerStore()
    {
        $storeA = $this->objectManager->get(StoreRepositoryInterface::class)->get('default')->getId();
        $storeB = $this->objectManager->get(StoreRepositoryInterface::class)->get('fixture_second_store')->getId();

        $encryptorMock = $this->createMock(EncryptorInterface::class);
        $encryptorMock->method('decrypt')->willReturn('keyA', 'keyB');

        /** @var General $instance */
        $instance = $this->objectManager->create(General::class, [
            'encryptor' => $encryptorMock,
        ]);

        $this->assertEquals('keyA', $instance->getApiKey($storeA));
        $this->assertEquals('keyB', $instance->getApiKey($storeB));
    }

    public function getMethodCodeDataProvider()
    {
        return [
            'paymentlink' => ['mollie_methods_paymentlink', ''],
            'checkmo' => ['checkmo', ''],
            'free' => ['free', ''],

            'applepay' => ['mollie_methods_applepay', 'applepay'],
            'alma' => ['mollie_methods_alma', 'alma'],
            'bancomatpay' => ['mollie_methods_bancomatpay', 'bancomatpay'],
            'bancontact' => ['mollie_methods_bancontact', 'bancontact'],
            'banktransfer' => ['mollie_methods_banktransfer', 'banktransfer'],
            'belfius' => ['mollie_methods_belfius', 'belfius'],
            'billie' => ['mollie_methods_billie', 'billie'],
            'bizum' => ['mollie_methods_bizum', 'bizum'],
            'blik' => ['mollie_methods_blik', 'blik'],
            'creditcard' => ['mollie_methods_creditcard', 'creditcard'],
            'directdebit' => ['mollie_methods_directdebit', 'directdebit'],
            'eps' => ['mollie_methods_eps', 'eps'],
            'giftcard' => ['mollie_methods_giftcard', 'giftcard'],
            'googlepay' => ['mollie_methods_googlepay', 'creditcard'],
            'ideal' => ['mollie_methods_ideal', 'ideal'],
            'in3' => ['mollie_methods_in3', 'in3'],
            'kbc' => ['mollie_methods_kbc', 'kbc'],
            'klarna' => ['mollie_methods_klarna', 'klarna'],
            'klarnapaylater' => ['mollie_methods_klarnapaylater', 'klarnapaylater'],
            'klarnapaynow' => ['mollie_methods_klarnapaynow', 'klarnapaynow'],
            'klarnasliceit' => ['mollie_methods_klarnasliceit', 'klarnasliceit'],
            'voucher' => ['mollie_methods_voucher', 'voucher'],
            'mbway' => ['mollie_methods_mbway', 'mbway'],
            'multibanco' => ['mollie_methods_multibanco', 'multibanco'],
            'mybank' => ['mollie_methods_mybank', 'mybank'],
            'paybybank' => ['mollie_methods_paybybank', 'paybybank'],
            'paypal' => ['mollie_methods_paypal', 'paypal'],
            'paysafecard' => ['mollie_methods_paysafecard', 'paysafecard'],
            'pointofsale' => ['mollie_methods_pointofsale', 'pointofsale'],
            'payconiq' => ['mollie_methods_payconiq', 'payconiq'],
            'przelewy24' => ['mollie_methods_przelewy24', 'przelewy24'],
            'riverty' => ['mollie_methods_riverty', 'riverty'],
            'satispay' => ['mollie_methods_satispay', 'satispay'],
            'sofort' => ['mollie_methods_sofort', 'sofort'],
            'swish' => ['mollie_methods_swish', 'swish'],
            'trustly' => ['mollie_methods_trustly', 'trustly'],
            'twint' => ['mollie_methods_twint', 'twint'],
        ];
    }

    /**
     * @dataProvider getMethodCodeDataProvider
     */
    public function testGetMethodCode($input, $expected)
    {
        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);

        /** @var OrderPaymentInterface $payment */
        $payment = $this->objectManager->create(OrderPaymentInterface::class);
        $order->setPayment($payment);

        $payment->setMethod($input);

        /** @var General $instance */
        $instance = $this->objectManager->create(General::class);
        $result = $instance->getMethodCode($order);

        $this->assertEquals($expected, $result);
    }
}
