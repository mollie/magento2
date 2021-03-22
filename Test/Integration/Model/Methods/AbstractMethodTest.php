<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Mollie\Api\Endpoints\MethodEndpoint;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Method;
use Mollie\Api\Resources\MethodCollection;
use Mollie\Payment\Helper\General as MollieHelper;
use Mollie\Payment\Model\MollieConfigProvider;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

abstract class AbstractMethodTest extends IntegrationTestCase
{
    /**
     * The class to test.
     *
     * @var Object
     */
    protected $instance;

    /**
     * @var string
     */
    protected $code = '';

    public function testHasAnExistingModel()
    {
        $this->assertTrue(class_exists($this->instance), 'We expect that the class ' . $this->instance . ' exists');
    }

    public function testHasTheCorrectCode()
    {
        /**
         * The parent constructor of this class calls the ObjectManager, which isn't available in unit tests. So skip
         * the constructor.
         */
        $reflection = new \ReflectionClass($this->instance);
        $instance = $reflection->newInstanceWithoutConstructor();

        $this->assertEquals('mollie_methods_' . $this->code, $instance->getCode());
    }

    public function testIsListedAsActiveMethod()
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturn(1);

        $context = $this->objectManager->create(Context::class, [
            'scopeConfig' => $scopeConfig,
        ]);

        /** @var MollieHelper $helper */
        $helper = $this->objectManager->create(MollieHelper::class, [
            'context' => $context,
        ]);

        $methods = $helper->getAllActiveMethods(1);

        if ($this->code == 'paymentlink') {
            $this->assertArrayNotHasKey('mollie_methods_' . $this->code, $methods);
            return;
        }

        $this->assertArrayHasKey('mollie_methods_' . $this->code, $methods);
    }

    public function testThatTheMethodIsActive()
    {
        $mollieHelperMock = $this->createMock(\Mollie\Payment\Helper\General::class);
        $mollieHelperMock->method('getOrderAmountByQuote')->willReturn(['value' => 100, 'currency' => 'EUR']);

        /** @var Method $method */
        $method = $this->objectManager->create(Method::class);
        $method->id = $this->code;
        $method->image = new \stdClass();
        $method->image->size2x = 'http://www.example.com/image.png';

        /** @var MethodCollection $methodCollection */
        $methodCollection = $this->objectManager->create(MethodCollection::class, ['count' => 0, '_links' => 0]);
        $methodCollection[] = $method;

        $mollieApiClient = $this->createMock(MollieApiClient::class);
        $mollieApiClient->methods = $this->createMock(MethodEndpoint::class);
        $mollieApiClient->methods->method('allActive')->willReturn($methodCollection);

        /** @var MollieConfigProvider $instance */
        $instance = $this->objectManager->create(MollieConfigProvider::class, [
            'mollieHelper' => $mollieHelperMock,
        ]);
        $methods = $instance->getActiveMethods($mollieApiClient);

        $this->assertArrayHasKey('mollie_methods_' . $this->code, $methods);
        $this->assertEquals($method->image->size2x, $methods['mollie_methods_' . $this->code]['image']);
    }
}
