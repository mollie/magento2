<?php

namespace Mollie\Payment\Test\Integration\Model;

use Mollie\Payment\Model\MollieConfigProvider;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class MollieConfigProviderTest extends IntegrationTestCase
{
    /**
     * @magentoConfigFixture default_store payment/mollie_general/enabled 1
     * @magentoConfigFixture default_store payment/mollie_general/apikey_test test_TEST_API_KEY
     * @magentoConfigFixture default_store payment/mollie_general/type test
     *
     * @magentoConfigFixture default_store payment/mollie_methods_bancontact/active 1
     * @magentoConfigFixture default_store payment/mollie_methods_bancontact/active 1
     * @magentoConfigFixture default_store payment/mollie_methods_banktransfer/active 1
     * @magentoConfigFixture default_store payment/mollie_methods_belfius/active 1
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/active 1
     * @magentoConfigFixture default_store payment/mollie_methods_paypal/active 1
     * @magentoConfigFixture default_store payment/mollie_methods_paysafecard/active 1
     * @magentoConfigFixture default_store payment/mollie_methods_sofort/active 1
     * @magentoConfigFixture default_store payment/mollie_methods_inghomepay/active 1
     * @magentoConfigFixture default_store payment/mollie_methods_giropay/active 1
     * @magentoConfigFixture default_store payment/mollie_methods_eps/active 1
     * @magentoConfigFixture default_store payment/mollie_methods_klarnapaylater/active 1
     * @magentoConfigFixture default_store payment/mollie_methods_klarnasliceit/active 1
     * @magentoConfigFixture default_store payment/mollie_methods_przelewy24/active 1
     * @magentoConfigFixture default_store payment/mollie_methods_applepay/active 1
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/active 1
     * @magentoConfigFixture default_store payment/mollie_methods_kbc/active 1
     * @magentoConfigFixture default_store payment/mollie_methods_giftcard/active 1
     */
    public function testGetConfig()
    {
        /** @var MollieConfigProvider $instance */
        $instance = $this->objectManager->get(MollieConfigProvider::class);

        $result = $instance->getConfig();

        $this->assertCount(17, $result['payment']['instructions']);
        $this->assertCount(17, $result['payment']['image']);

        $this->assertArrayHasKey('mollie_methods_ideal', $result['payment']['issuersListType']);
        $this->assertArrayHasKey('mollie_methods_kbc', $result['payment']['issuersListType']);
        $this->assertArrayHasKey('mollie_methods_giftcard', $result['payment']['issuersListType']);

        $this->assertEquals([], $result['payment']['issuers']['mollie_methods_ideal']);
        $this->assertEquals([], $result['payment']['issuers']['mollie_methods_kbc']);
        $this->assertEquals([], $result['payment']['issuers']['mollie_methods_giftcard']);
    }
}
