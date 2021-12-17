<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Controller\Adminhtml\Action;

use Magento\Framework\Encryption\Encryptor;
use Mollie\Api\Endpoints\MethodEndpoint;
use Mollie\Payment\Helper\Tests;
use Mollie\Payment\Model\Mollie;
use Mollie\Payment\Test\Integration\BackendControllerTestCase;

class ApikeyTest extends BackendControllerTestCase
{
    public function testValidatesTheTestKey()
    {
        $this->mockMollieMethodsEndpointForRequestKeys('test_apikey123456789101112131415161718', '');

        $this->dispatch('backend/mollie/action/apikey');

        $result = json_decode($this->getResponse()->getContent(), true);

        $this->assertStringContainsString('Test API-key: Success!', $result['msg']);
        $this->assertStringContainsString('Live API-key: Empty value', $result['msg']);
        $this->assertTrue($result['success']);
    }

    public function testValidatesTheLiveKey()
    {
        $this->mockMollieMethodsEndpointForRequestKeys('', 'live_apikey123456789101112131415161718');

        $this->dispatch('backend/mollie/action/apikey');

        $result = json_decode($this->getResponse()->getContent(), true);

        $this->assertStringContainsString('Test API-key: Empty value', $result['msg']);
        $this->assertStringContainsString('Live API-key: Success!', $result['msg']);
        $this->assertTrue($result['success']);
    }

    public function testFallsBackOnTheConfigurationForTest()
    {
        $encryptorMock = $this->createMock(Encryptor::class);

        $encryptorMock->method('decrypt')->willReturnCallback(function ($input) use (&$count) {
            $count++;
            if ($count == 2) {
                return 'test_apikey123456789101112131415161718';
            }

            if ($count === 3) {
                return '';
            }

            return $input;
        });

        $this->mockMollieMethodsEndpointForConfigurationKeys('test_apikey123456789101112131415161718', '');

        $this->_objectManager->addSharedInstance($encryptorMock, Encryptor::class);

        $this->dispatch('backend/mollie/action/apikey');

        $result = json_decode($this->getResponse()->getContent(), true);

        $this->assertStringContainsString('Test API-key: Success!', $result['msg']);
        $this->assertStringContainsString('Live API-key: Empty value', $result['msg']);
        $this->assertTrue($result['success']);
    }

    public function testFallsBackOnTheConfigurationForLive()
    {
        $count = 0;
        $encryptorMock = $this->createMock(Encryptor::class);
        $encryptorMock->method('decrypt')->willReturnCallback(function ($input) use (&$count) {
            $count++;
            if ($count == 2) {
                return '';
            }

            if ($count === 3) {
                return 'live_apikey123456789101112131415161718';
            }

            return $input;
        });

        $this->mockMollieMethodsEndpointForConfigurationKeys('', 'live_apikey123456789101112131415161718');

        $this->_objectManager->addSharedInstance($encryptorMock, Encryptor::class);

        $this->dispatch('backend/mollie/action/apikey');

        $result = json_decode($this->getResponse()->getContent(), true);

        $this->assertStringContainsString('Test API-key: Empty value', $result['msg']);
        $this->assertStringContainsString('Live API-key: Success!', $result['msg']);
        $this->assertTrue($result['success']);
    }

    protected function mockMollieMethodsEndpointForRequestKeys(string $testApiKey, string $liveApiKey): void
    {
        $mollieModel = $this->_objectManager->get(Mollie::class);
        $mollieModelMock = $this->createMock(Mollie::class);
        foreach (array_filter([$testApiKey, $liveApiKey]) as $key) {
            $api = $mollieModel->loadMollieApi($key);

            $api->methods = $this->createMock(MethodEndpoint::class);
            $api->methods->method('all')->willReturn([]);

            $mollieModelMock->method('loadMollieApi')->with($key)->willReturn($api);
        }

        $tests = $this->_objectManager->create(Tests::class, [
            'mollieModel' => $mollieModelMock,
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
        $mollieModel = $this->_objectManager->get(Mollie::class);
        $mollieModelMock = $this->createMock(Mollie::class);
        foreach (array_filter([$testApiKey, $liveApiKey]) as $key) {
            $api = $mollieModel->loadMollieApi($key);

            $api->methods = $this->createMock(MethodEndpoint::class);
            $api->methods->method('all')->willReturn([]);

            $mollieModelMock->method('loadMollieApi')->with($key)->willReturn($api);
        }

        $tests = $this->_objectManager->create(Tests::class, [
            'mollieModel' => $mollieModelMock,
            'tests' => [],
        ]);

        $this->_objectManager->addSharedInstance($tests, Tests::class);

        $this->getRequest()->setParams([
            'test_key' => '',
            'live_key' => '',
        ]);
    }
}
