<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Model;

use Magento\Framework\Locale\Resolver;
use Mollie\Api\Endpoints\MethodEndpoint;
use Mollie\Api\Resources\MethodCollection;
use Mollie\Payment\Config;
use Mollie\Payment\Helper\General;
use Mollie\Payment\Model\MollieConfigProvider;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class MollieConfigProviderTest extends IntegrationTestCase
{
    /**
     * @magentoConfigFixture default_store payment/mollie_general/enabled 1
     * @magentoConfigFixture default_store payment/mollie_general/apikey_test test_TEST_API_KEY_THAT_IS_LONG_ENOUGH
     * @magentoConfigFixture default_store payment/mollie_general/type test
     *
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/active 1
     * @magentoConfigFixture default_store payment/mollie_methods_kbc/active 1
     * @magentoConfigFixture default_store payment/mollie_methods_giftcard/active 1
     */
    public function testGetConfig()
    {
        $mollieHelperMock = $this->createMock(General::class);
        $mollieHelperMock->method('getApiKey')->willReturn('test_TEST_API_KEY_THAT_IS_LONG_ENOUGH');

        /** @var MollieConfigProvider $instance */
        $instance = $this->objectManager->create(MollieConfigProvider::class, [
            'mollieHelper' => $mollieHelperMock,
        ]);

        $result = $instance->getConfig();

//        $this->assertArrayHasKey('mollie_methods_kbc', $result['payment']['issuersListType']);
//        $this->assertArrayHasKey('mollie_methods_giftcard', $result['payment']['issuersListType']);

        $this->assertArrayHasKey('mollie_methods_applepay', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_alma', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_bancomatpay', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_bancontact', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_banktransfer', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_belfius', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_billie', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_blik', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_creditcard', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_directdebit', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_eps', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_giftcard', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_ideal', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_in3', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_kbc', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_klarna', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_klarnapaylater', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_klarnapaynow', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_klarnasliceit', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_mybank', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_paypal', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_paysafecard', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_pointofsale', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_payconiq', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_przelewy24', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_riverty', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_satispay', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_sofort', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_trustly', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_twint', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_voucher', $result['payment']['image']);

//        $this->assertEquals([], $result['payment']['issuers']['mollie_methods_kbc']);
//        $this->assertEquals([], $result['payment']['issuers']['mollie_methods_giftcard']);
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

    /**
     * @magentoConfigFixture default_store payment/mollie_general/locale nl_NL
     */
    public function testContainsTheLocale()
    {
        /** @var MollieConfigProvider $instance */
        $instance = $this->objectManager->create(MollieConfigProvider::class);
        $result = $instance->getConfig();

        $this->assertArrayHasKey('mollie', $result['payment']);
        $this->assertArrayHasKey('locale', $result['payment']['mollie']);

        $this->assertSame('nl_NL', $result['payment']['mollie']['locale']);
    }

    public function testWhenNoActiveMethodsAvailableTheResultIsAnEmptyArray()
    {
        $methodMock = $this->createMock(MethodEndpoint::class);
        $methodMock->method('allActive')->willReturn(new MethodCollection(0, new \stdClass));

        $api = new \Mollie\Api\MollieApiClient;
        $api->methods = $methodMock;

        /** @var MollieConfigProvider $instance */
        $instance = $this->objectManager->create(MollieConfigProvider::class);
        $result = $instance->getActiveMethods($api);

        $this->assertTrue(is_array($result), 'We expect an array');
        $this->assertCount(0, $result);
    }
}
