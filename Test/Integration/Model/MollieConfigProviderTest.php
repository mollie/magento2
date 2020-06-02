<?php

namespace Mollie\Payment\Test\Integration\Model;

use Magento\Framework\Locale\Resolver;
use Mollie\Payment\Config;
use Mollie\Payment\Model\MollieConfigProvider;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class MollieConfigProviderTest extends IntegrationTestCase
{
    /**
     * @magentoConfigFixture default_store payment/mollie_general/enabled 1
     * @magentoConfigFixture default_store payment/mollie_general/apikey_test test_TEST_API_KEY_THAT_IS_LONG_ENOUGH
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

        $this->assertCount(19, $result['payment']['instructions']);
        $this->assertCount(19, $result['payment']['image']);

        $this->assertArrayHasKey('mollie_methods_ideal', $result['payment']['issuersListType']);
        $this->assertArrayHasKey('mollie_methods_kbc', $result['payment']['issuersListType']);
        $this->assertArrayHasKey('mollie_methods_giftcard', $result['payment']['issuersListType']);

        $this->assertEquals([], $result['payment']['issuers']['mollie_methods_ideal']);
        $this->assertEquals([], $result['payment']['issuers']['mollie_methods_kbc']);
        $this->assertEquals([], $result['payment']['issuers']['mollie_methods_giftcard']);
    }

    public function testConfigContainsTheUseComponentsValue()
    {
        $configMock = $this->createMock(Config::class);
        $useComponents = (bool)rand(0, 1);
        $configMock->method('creditcardUseComponents')->willReturn($useComponents);

        /** @var MollieConfigProvider $instance */
        $instance = $this->objectManager->create(MollieConfigProvider::class, ['config' => $configMock]);
        $result = $instance->getConfig();

        $this->assertArrayHasKey('mollie', $result['payment']);
        $this->assertArrayHasKey('creditcard', $result['payment']['mollie']);
        $this->assertArrayHasKey('use_components', $result['payment']['mollie']['creditcard']);

        $this->assertSame($useComponents, $result['payment']['mollie']['creditcard']['use_components']);
    }

    public function configContainsGeneralSettingsProvider()
    {
        return [
            ['testmode', 'isTestMode', rand(0, 1) ? 'live' : 'test'],
            ['profile_id', 'getProfileId', 'ProfileId' . uniqid()],
        ];
    }

    /**
     * @dataProvider configContainsGeneralSettingsProvider
     */
    public function testConfigContainsGeneralSettings($key, $method, $expected)
    {
        $configMock = $this->createMock(Config::class);
        $configMock->method($method)->willReturn($expected);

        /** @var MollieConfigProvider $instance */
        $instance = $this->objectManager->create(MollieConfigProvider::class, ['config' => $configMock]);
        $result = $instance->getConfig();

        $this->assertArrayHasKey('mollie', $result['payment']);
        $this->assertArrayHasKey($key, $result['payment']['mollie']);

        $this->assertSame($expected, $result['payment']['mollie'][$key]);
    }

    public function testContainsTheLocale()
    {
        $localeResolverMock = $this->createMock(Resolver::class);
        $localeResolverMock->method('getLocale')->willReturn('nl_NL');

        /** @var MollieConfigProvider $instance */
        $instance = $this->objectManager->create(MollieConfigProvider::class, ['localeResolver' => $localeResolverMock]);
        $result = $instance->getConfig();

        $this->assertArrayHasKey('mollie', $result['payment']);
        $this->assertArrayHasKey('locale', $result['payment']['mollie']);

        $this->assertSame('nl_NL', $result['payment']['mollie']['locale']);
    }
}
