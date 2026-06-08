<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Controller\Adminhtml\Action;

use Mollie\Api\Fake\MockResponse;
use Mollie\Api\Http\Requests\GetEnabledMethodsRequest;
use Mollie\Api\Http\Requests\GetPaginatedTerminalsRequest;
use Mollie\Api\MollieApiClient;
use Mollie\Payment\Helper\Tests;
use Mollie\Payment\Test\Integration\BackendControllerTestCase;

class ApikeyTest extends BackendControllerTestCase
{
    public function testValidatesTheTestKey(): void
    {
        $this->mockMollieMethodsEndpointForRequestKeys('test_apikey123456789101112131415161718', '');

        $this->getRequest()->setMethod('POST');
        $this->dispatch('backend/mollie/action/apikey');

        $result = json_decode($this->getResponse()->getContent(), true);

        $this->assertStringContainsString('Test API-key: Success!', $result['msg']);
        $this->assertStringContainsString('Live API-key: Empty value', $result['msg']);
        $this->assertTrue($result['success']);
    }

    public function testValidatesTheLiveKey(): void
    {
        $this->mockMollieMethodsEndpointForRequestKeys('', 'live_apikey123456789101112131415161718');

        $this->getRequest()->setMethod('POST');
        $this->dispatch('backend/mollie/action/apikey');

        $result = json_decode($this->getResponse()->getContent(), true);

        $this->assertStringContainsString('Test API-key: Empty value', $result['msg']);
        $this->assertStringContainsString('Live API-key: Success!', $result['msg']);
        $this->assertTrue($result['success']);
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_general/apikey_test test_apikey123456789101112131415161718
     * @return void
     */
    public function testFallsBackOnTheConfigurationForTest(): void
    {
        $this->mockMollieMethodsEndpointForConfigurationKeys('test_apikey123456789101112131415161718', '');

        $this->getRequest()->setMethod('POST');
        $this->dispatch('backend/mollie/action/apikey');

        $result = json_decode($this->getResponse()->getContent(), true);

        $this->assertStringContainsString('Test API-key: Success!', $result['msg']);
        $this->assertStringContainsString('Live API-key: Empty value', $result['msg']);
        $this->assertTrue($result['success']);
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_general/apikey_live live_apikey123456789101112131415161718
     * @return void
     */
    public function testFallsBackOnTheConfigurationForLive(): void
    {
        $this->mockMollieMethodsEndpointForConfigurationKeys('', 'live_apikey123456789101112131415161718');

        $this->getRequest()->setMethod('POST');
        $this->dispatch('backend/mollie/action/apikey');

        $result = json_decode($this->getResponse()->getContent(), true);

        $this->assertStringContainsString('Test API-key: Empty value', $result['msg']);
        $this->assertStringContainsString('Live API-key: Success!', $result['msg']);
        $this->assertTrue($result['success']);
    }

    protected function mockMollieMethodsEndpointForRequestKeys(string $testApiKey, string $liveApiKey): void
    {
        $mollieModelMock = $this->createMock(\Mollie\Payment\Service\Mollie\MollieApiClient::class);
        foreach (array_filter([$testApiKey, $liveApiKey]) as $key) {
            $client = MollieApiClient::fake([
                GetEnabledMethodsRequest::class => MockResponse::ok('method-list'),
                GetPaginatedTerminalsRequest::class => MockResponse::ok('terminal-list'),
            ]);

            $mollieModelMock->method('loadByApiKey')->with($key)->willReturn($client);
        }

        $tests = $this->_objectManager->create(Tests::class, [
            'mollieApiClient' => $mollieModelMock,
            'tests' => [],
        ]);

        $this->_objectManager->addSharedInstance($tests, Tests::class);

        $this->getRequest()->setParams([
            'test_key' => $testApiKey,
            'live_key' => $liveApiKey,
        ]);
    }

    protected function mockMollieMethodsEndpointForConfigurationKeys(string $testApiKey, string $liveApiKey): void
    {
        $mollieModelMock = $this->createMock(\Mollie\Payment\Service\Mollie\MollieApiClient::class);
        foreach (array_filter([$testApiKey, $liveApiKey]) as $key) {
            $client = MollieApiClient::fake([
                GetEnabledMethodsRequest::class => MockResponse::ok('method-list'),
                GetPaginatedTerminalsRequest::class => MockResponse::ok('terminal-list'),
            ]);

            $mollieModelMock->method('loadByApiKey')->with($key)->willReturn($client);
        }

        $tests = $this->_objectManager->create(Tests::class, [
            'mollieApiClient' => $mollieModelMock,
            'tests' => [],
        ]);

        $this->_objectManager->addSharedInstance($tests, Tests::class);

        $this->getRequest()->setParams([
            'test_key' => '',
            'live_key' => '',
        ]);
    }
}
