<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Controller\Checkout;

use Exception;
use Magento\TestFramework\Request;
use Magento\TestFramework\TestCase\AbstractController as ControllerTestCase;
use Mollie\Payment\Model\Mollie;

class WebhookTest extends ControllerTestCase
{
    public function testSetsTheStatusCodeTo503WhenTheOrderProcessFails(): void
    {
        $mollieModel = $this->createMock(Mollie::class);
        $mollieModel->method('getOrderIdsByTransactionId')->willReturn([123]);
        $mollieModel->method('processTransaction')->willThrowException(new Exception('[TEST] Transaction failed. Please verify your billing information and payment method, and try again.'));

        $this->_objectManager->addSharedInstance($mollieModel, Mollie::class);

        $this->getRequest()->setMethod(Request::METHOD_POST);
        $this->getRequest()->setParams([
            'id' => 'ord_123ABC',
        ]);

        $this->dispatch('mollie/checkout/webhook');

        $this->assertSame(503, $this->getResponse()->getHttpResponseCode());
    }

    public function testTheTestByMollieReturnsAnOkResponse(): void
    {
        $this->getRequest()->setParam('testByMollie', true);

        $this->dispatch('mollie/checkout/webhook');

        $this->assertOkResponse();
    }

    public function testReturns200IfNoTransactionIdProvided(): void
    {
        $this->dispatch('mollie/checkout/webhook');

        $this->assertEquals(200, $this->getResponse()->getStatusCode());
    }

    public function testReturns200IfAnInvalidTransactionIdIsProvided(): void
    {
        $this->getRequest()->setParam('id', 'NON_EXISTING');

        $this->dispatch('mollie/checkout/webhook');

        $this->assertEquals(200, $this->getResponse()->getStatusCode());
    }

    private function assertOkResponse(): void
    {
        $this->assertEquals(200, $this->getResponse()->getStatusCode());
        $this->assertEquals('OK', $this->getResponse()->getContent());
    }
}
