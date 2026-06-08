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
use Mollie\Api\Fake\MockResponse;
use Mollie\Api\Http\Requests\GetPaymentRequest;
use Mollie\Payment\Service\Magento\GetOrderIdsByTransactionId;
use Mollie\Payment\Service\Mollie\MollieApiClient;
use Mollie\Payment\Test\Fakes\Service\Mollie\FakeMollieApiClient;

class WebhookTest extends ControllerTestCase
{
    public function testSetsTheStatusCodeTo503WhenTheOrderProcessFails(): void
    {
        $getOrdersByTransactionId = $this->createMock(GetOrderIdsByTransactionId::class);
        $getOrdersByTransactionId
            ->method('execute')
            ->willThrowException(new Exception(
                '[TEST] Transaction failed. Please verify your billing information and payment method, and try again.'
            ));

        $this->_objectManager->addSharedInstance($getOrdersByTransactionId, GetOrderIdsByTransactionId::class);

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
        /** @var FakeMollieApiClient $fakeMollieApiClient */
        $fakeMollieApiClient = $this->_objectManager->get(FakeMollieApiClient::class);
        $fakeMollieApiClient->fake([GetPaymentRequest::class => MockResponse::ok()]);
        $this->_objectManager->addSharedInstance($fakeMollieApiClient, MollieApiClient::class);

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
