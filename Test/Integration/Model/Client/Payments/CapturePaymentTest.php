<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Model\Client\Payments;

use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Service\InvoiceService;
use Mollie\Api\Endpoints\PaymentCaptureEndpoint;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Capture;
use Mollie\Payment\Model\Client\Payments\CapturePayment;
use Mollie\Payment\Test\Fakes\Service\Mollie\FakeMollieApiClient;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class CapturePaymentTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testSetsTransactionIdOnInvoice(): void
    {
        $mollieTransactionId = 'tr_abc123';

        $order = $this->loadOrder('100000001');
        $order->setMollieTransactionId($mollieTransactionId);
        $order->getPayment()->setIsTransactionPending(true);

        /** @var InvoiceService $invoiceService */
        $invoiceService = $this->objectManager->get(InvoiceService::class);
        $invoice = $invoiceService->prepareInvoice($order);
        $invoice->setRequestedCaptureCase(Invoice::NOT_CAPTURE);
        $invoice->register();

        $capture = new Capture($this->createStub(MollieApiClient::class));
        $capture->id = 'cpt_xyz789';

        $paymentCapturesStub = $this->createStub(PaymentCaptureEndpoint::class);
        $paymentCapturesStub->method('createForId')->willReturn($capture);

        $mollieApiMock = $this->createStub(MollieApiClient::class);
        $mollieApiMock->paymentCaptures = $paymentCapturesStub;

        /** @var FakeMollieApiClient $fakeMollieApiClient */
        $fakeMollieApiClient = $this->objectManager->get(FakeMollieApiClient::class);
        $fakeMollieApiClient->setInstance($mollieApiMock);
        $this->objectManager->addSharedInstance($fakeMollieApiClient, \Mollie\Payment\Service\Mollie\MollieApiClient::class);

        /** @var CapturePayment $instance */
        $instance = $this->objectManager->create(CapturePayment::class);
        $instance->execute($invoice);

        $this->assertEquals($mollieTransactionId, $invoice->getTransactionId());
    }
}
