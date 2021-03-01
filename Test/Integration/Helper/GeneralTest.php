<?php

namespace Mollie\Payment\Test\Integration\Helper;

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

        /** @var General $instance */
        $instance = $this->objectManager->create(General::class);

        $this->assertEquals('keyA', $instance->getApiKey($storeA));
        $this->assertEquals('keyB', $instance->getApiKey($storeB));
    }
}
