<?php

namespace Mollie\Payment\Plugin\Framework\App\Request;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Request\CsrfValidator;
use Magento\Framework\UrlInterface;
use Mollie\Payment\Test\Unit\UnitTestCase;

class CsrfValidatorSkipTest extends UnitTestCase
{
    protected function setUp()
    {
        parent::setUp();

        if (!class_exists(CsrfValidator::class)) {
            $this->markTestSkipped('The class ' . CsrfValidator::class . ' is only available on Magento 2.3 and later');
        }

        if (getenv('CI')) {
            $this->markTestSkipped('Fails on CI');
        }
    }

    public function testCallsTheProcessWhenNotMollie()
    {
        $urlMock = $this->createMock(UrlInterface::class);
        $urlMock->method('getCurrentUrl')->willReturn('http://www.example.com/magento/dummy/dummy');

        /** @var CsrfValidatorSkip $instance */
        $instance = $this->objectManager->getObject(CsrfValidatorSkip::class, [
            'url' => $urlMock,
        ]);

        $called = false;
        $instance->aroundValidate(
            $this->objectManager->getObject(CsrfValidator::class),
            function () use (&$called) {
                $called = true;
            },
            $this->getRequestInterface(),
            $this->getActionInterface()
        );

        $this->assertTrue($called);
    }

    public function testCallsTheProcessWhenMollie()
    {
        $urlMock = $this->createMock(UrlInterface::class);
        $urlMock->method('getCurrentUrl')->willReturn('http://www.example.com/mollie/checkout/webhook/?ajax=1');

        /** @var CsrfValidatorSkip $instance */
        $instance = $this->objectManager->getObject(CsrfValidatorSkip::class, [
            'url' => $urlMock,
        ]);

        $called = false;
        $instance->aroundValidate(
            $this->objectManager->getObject(CsrfValidator::class),
            function () use (&$called) {
                $called = true;
                $this->fail('The $proceed() function should not be called');
            },
            $this->getRequestInterface(),
            $this->getActionInterface()
        );

        $this->assertFalse($called);
    }

    public function testReturnsTheValueOfProceed()
    {
        /** @var CsrfValidatorSkip $instance */
        $instance = $this->objectManager->getObject(CsrfValidatorSkip::class);

        $uniqid = uniqid();
        $result = $instance->aroundValidate(
            $this->objectManager->getObject(CsrfValidator::class),
            function () use ($uniqid) {
                return $uniqid;
            },
            $this->getRequestInterface(),
            $this->getActionInterface()
        );

        $this->assertEquals($uniqid, $result);
    }

    private function getRequestInterface()
    {
        return new class implements \Magento\Framework\App\RequestInterface {
            public function getModuleName(){}
            public function setModuleName($name){}
            public function getActionName(){}
            public function setActionName($name){}
            public function getParam($key, $defaultValue = null){}
            public function setParams(array $params){}
            public function getParams(){}
            public function getCookie($name, $default){}
            public function isSecure(){}
        };
    }

    /**
     * @return ActionInterface|__anonymous@3452
     */
    private function getActionInterface()
    {
        return new class implements ActionInterface{
            public function execute() {}
        };
    }
}
