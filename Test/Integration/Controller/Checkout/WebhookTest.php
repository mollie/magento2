<?php

namespace Mollie\Payment\Test\Integration\Controller\Checkout;

use Magento\TestFramework\Request;
use Magento\TestFramework\TestCase\AbstractController as ControllerTestCase;
use Mollie\Payment\Model\Mollie;

class WebhookTest extends ControllerTestCase
{
    public function testSetsTheStatusCodeTo503WhenTheOrderProcessFails()
    {
        $mollieModel = $this->createMock(Mollie::class);
        $mollieModel->method('getOrderIdsByTransactionId')->willReturn([123]);
        $mollieModel->method('processTransaction')->willThrowException(new \Exception('[TEST] Something went wrong'));

        $this->_objectManager->addSharedInstance($mollieModel, Mollie::class);

        $this->getRequest()->setMethod(Request::METHOD_POST);
        $this->getRequest()->setParams([
            'id' => 'ord_123ABC',
        ]);

        $this->dispatch('mollie/checkout/webhook');

        $this->assertSame(200, $this->getResponse()->getHttpResponseCode());
    }

    public function testTheTestByMollieReturnsAnOkResponse()
    {
        $this->getRequest()->setParam('testByMollie', true);

        $this->dispatch('mollie/checkout/webhook');

        $this->assertOkResponse();
    }

    public function testReturns404IfNoTransactionIdProvided()
    {
        $this->dispatch('mollie/checkout/webhook');

        $this->assertEquals(200, $this->getResponse()->getStatusCode());
    }

    public function testReturns404IfAnInvalidTransactionIdIsProvided()
    {
        $this->getRequest()->setParam('id', 'NON_EXISTING');

        $this->dispatch('mollie/checkout/webhook');

        $this->assertEquals(200, $this->getResponse()->getStatusCode());
    }

    private function assertOkResponse()
    {
        $this->assertEquals(200, $this->getResponse()->getStatusCode());
        $this->assertEquals('OK', $this->getResponse()->getContent());
    }
}
