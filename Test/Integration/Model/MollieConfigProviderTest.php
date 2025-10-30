<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Model;

use Mollie\Api\Fake\MockResponse;
use Mollie\Api\Http\Requests\GetEnabledMethodsRequest;
use Mollie\Api\MollieApiClient;
use Mollie\Payment\Config;
use Mollie\Payment\Helper\General;
use Mollie\Payment\Model\MollieConfigProvider;
use Mollie\Payment\Test\Fakes\Service\Mollie\FakeMollieApiClient;
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
    public function testGetConfig(): void
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
        $this->assertArrayHasKey('mollie_methods_bizum', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_blik', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_creditcard', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_directdebit', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_eps', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_giftcard', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_googlepay', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_ideal', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_in3', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_kbc', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_klarna', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_mbway', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_mobilepay', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_multibanco', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_mybank', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_paybybank', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_paypal', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_paysafecard', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_pointofsale', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_payconiq', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_przelewy24', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_riverty', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_satispay', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_sofort', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_swish', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_trustly', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_twint', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_vipps', $result['payment']['image']);
        $this->assertArrayHasKey('mollie_methods_voucher', $result['payment']['image']);

        //        $this->assertEquals([], $result['payment']['issuers']['mollie_methods_kbc']);
        //        $this->assertEquals([], $result['payment']['issuers']['mollie_methods_giftcard']);
    }

    public function testConfigContainsTheUseComponentsValue(): void
    {
        $configMock = $this->createMock(Config::class);
        $useComponents = (bool) rand(0, 1);
        $configMock->method('isModuleEnabled')->willReturn(true);
        $configMock->method('creditcardUseComponents')->willReturn($useComponents);

        $api = new MollieApiClient();

        /** @var FakeMollieApiClient $fakeMollieApiClient */
        $fakeMollieApiClient = $this->objectManager->get(FakeMollieApiClient::class);
        $fakeMollieApiClient->setInstance($api);
        $this->objectManager->addSharedInstance($fakeMollieApiClient, \Mollie\Payment\Service\Mollie\MollieApiClient::class);

        /** @var MollieConfigProvider $instance */
        $instance = $this->objectManager->create(MollieConfigProvider::class, ['config' => $configMock]);
        $result = $instance->getConfig();

        $this->assertArrayHasKey('mollie', $result['payment']);
        $this->assertArrayHasKey('creditcard', $result['payment']['mollie']);
        $this->assertArrayHasKey('use_components', $result['payment']['mollie']['creditcard']);

        $this->assertSame($useComponents, $result['payment']['mollie']['creditcard']['use_components']);
    }

    public function configContainsGeneralSettingsProvider(): array
    {
        return [
            ['testmode', 'isTestMode', (bool) rand(0, 1)],
            ['profile_id', 'getProfileId', 'ProfileId' . uniqid()],
        ];
    }

    /**
     * @dataProvider configContainsGeneralSettingsProvider
     */
    public function testConfigContainsGeneralSettings(string $key, string $method, bool|string $expected): void
    {
        $configMock = $this->createMock(Config::class);
        $configMock->method('isModuleEnabled')->willReturn(true);
        $configMock->method($method)->willReturn($expected);

        /** @var MollieConfigProvider $instance */
        $instance = $this->objectManager->create(MollieConfigProvider::class, ['config' => $configMock]);
        $result = $instance->getConfig();

        $this->assertArrayHasKey('mollie', $result['payment']);
        $this->assertArrayHasKey($key, $result['payment']['mollie']);

        $this->assertSame($expected, $result['payment']['mollie'][$key]);
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_general/enabled 1
     * @magentoConfigFixture default_store payment/mollie_general/locale nl_NL
     */
    public function testContainsTheLocale(): void
    {
        /** @var MollieConfigProvider $instance */
        $instance = $this->objectManager->create(MollieConfigProvider::class);
        $result = $instance->getConfig();

        $this->assertArrayHasKey('mollie', $result['payment']);
        $this->assertArrayHasKey('locale', $result['payment']['mollie']);

        $this->assertSame('nl_NL', $result['payment']['mollie']['locale']);
    }

    public function testWhenNoActiveMethodsAvailableTheResultIsAnEmptyArray(): void
    {
        $client = MollieApiClient::fake([
            GetEnabledMethodsRequest::class => MockResponse::ok('{}'),
        ]);

        /** @var FakeMollieApiClient $fakeMollieApiClient */
        $fakeMollieApiClient = $this->objectManager->get(FakeMollieApiClient::class);
        $fakeMollieApiClient->setInstance($client);
        $this->objectManager->addSharedInstance($fakeMollieApiClient, \Mollie\Payment\Service\Mollie\MollieApiClient::class);

        /** @var MollieConfigProvider $instance */
        $instance = $this->objectManager->create(MollieConfigProvider::class);
        $result = $instance->getActiveMethods();

        $this->assertTrue(is_array($result), 'We expect an array');
        $this->assertCount(0, $result);
    }
}
