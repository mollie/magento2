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
        $mollieModel->method('getOrderIdByTransactionId')->willReturn(123);
        $mollieModel->method('processTransaction')->willThrowException(new \Exception('[TEST] Something went wrong'));

        $this->_objectManager->addSharedInstance($mollieModel, Mollie::class);

        $this->getRequest()->setMethod(Request::METHOD_POST);
        $this->getRequest()->setParams([
            'id' => 'ord_123ABC',
        ]);

        $this->dispatch('mollie/checkout/webhook');

        $this->assertSame(503, $this->getResponse()->getHttpResponseCode());
    }
}
