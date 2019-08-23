<?php

namespace Mollie\Payment\Plugin\Framework\App\Request;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Request\CsrfValidator;
use Magento\Framework\App\RequestInterface;
use Mollie\Payment\Test\Unit\UnitTestCase;

class CsrfValidatorSkipTest extends UnitTestCase
{
    public function testCallsTheProcessWhenNotMollie()
    {
        if (!class_exists(CsrfValidator::class)) {
            $this->markTestSkipped('The class ' . CsrfValidator::class . ' is only available on Magento 2.3 and later');
        }

        $csrfValidator = $this->objectManager->getObject(CsrfValidator::class);
        $actionMock = $this->createMock(ActionInterface::class);

        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->method('getModuleName')->willReturn('magento');

        /** @var CsrfValidatorSkip $instance */
        $instance = $this->objectManager->getObject(CsrfValidatorSkip::class);

        $called = false;
        $instance->aroundValidate(
            $csrfValidator,
            function () use (&$called) {
                $called = true;
            },
            $requestMock,
            $actionMock
        );

        $this->assertTrue($called);
    }

    public function testCallsTheProcessWhenMollie()
    {
        if (!class_exists(CsrfValidator::class)) {
            $this->markTestSkipped('The class ' . CsrfValidator::class . ' is only available on Magento 2.3 and later');
        }

        $csrfValidator = $this->objectManager->getObject(CsrfValidator::class);
        $actionMock = $this->createMock(ActionInterface::class);

        $requestMock = $this->createMock(RequestInterface::class);
        $requestMock->method('getModuleName')->willReturn('mollie');

        /** @var CsrfValidatorSkip $instance */
        $instance = $this->objectManager->getObject(CsrfValidatorSkip::class);

        $called = false;
        $instance->aroundValidate(
            $csrfValidator,
            function () use (&$called) {
                $called = true;
                $this->fail('The $proceed() function should not be called');
            },
            $requestMock,
            $actionMock
        );

        $this->assertFalse($called);
    }
}
