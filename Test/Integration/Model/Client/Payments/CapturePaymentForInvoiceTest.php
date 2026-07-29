<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Model\Client\Payments;

use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Model\Order\Invoice;
use Mollie\Api\Fake\MockResponse;
use Mollie\Api\Http\Requests\CreatePaymentCaptureRequest;
use Mollie\Payment\Exceptions\PartialCaptureNotSupported;
use Mollie\Payment\Model\Client\Payments\CapturePaymentForInvoice;
use Mollie\Payment\Test\Fakes\Service\Mollie\FakeMollieApiClient;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class CapturePaymentForInvoiceTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testRefusesAPartialInvoiceWhenTheMethodDoesNotSupportPartialCaptures(): void
    {
        $this->fakeCaptureApi();

        $instance = $this->objectManager->create(CapturePaymentForInvoice::class);

        $this->expectException(PartialCaptureNotSupported::class);
        $instance->execute($this->prepareInvoice('mollie_methods_riverty', 44.0));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testCallsNoApiForARefusedPartialInvoice(): void
    {
        $fake = $this->fakeCaptureApi();

        $instance = $this->objectManager->create(CapturePaymentForInvoice::class);

        try {
            $instance->execute($this->prepareInvoice('mollie_methods_riverty', 44.0));
            $this->fail('Expected the partial invoice to be refused');
        } catch (PartialCaptureNotSupported $exception) {
            $fake->loadByStore()->assertSentCount(0);
        }
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testCapturesAnInvoiceThatCoversTheCompleteOrder(): void
    {
        $fake = $this->fakeCaptureApi();

        $instance = $this->objectManager->create(CapturePaymentForInvoice::class);
        $instance->execute($this->prepareInvoice('mollie_methods_riverty', 100.0));

        $fake->loadByStore()->assertSent(CreatePaymentCaptureRequest::class);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testCapturesAPartialInvoiceWhenTheMethodSupportsPartialCaptures(): void
    {
        $fake = $this->fakeCaptureApi();

        $instance = $this->objectManager->create(CapturePaymentForInvoice::class);
        $instance->execute($this->prepareInvoice('mollie_methods_creditcard', 44.0));

        $fake->loadByStore()->assertSent(CreatePaymentCaptureRequest::class);
    }

    private function fakeCaptureApi(): FakeMollieApiClient
    {
        $fake = $this->loadFakeMollieApiClient();
        $fake->fake([
            CreatePaymentCaptureRequest::class => MockResponse::created(
                '{"resource":"capture","id":"cpt_dummycapture","paymentId":"tr_dummytransaction","status":"pending"}',
            ),
        ], true);

        return $fake;
    }

    private function prepareInvoice(string $method, float $invoiceGrandTotal): InvoiceInterface
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod($method);
        $order->setMollieTransactionId('tr_dummytransaction');
        $order->setBaseGrandTotal(100.0);

        /** @var Invoice $invoice */
        $invoice = $this->objectManager->create(Invoice::class);
        $invoice->setOrder($order);
        $invoice->setBaseGrandTotal($invoiceGrandTotal);

        return $invoice;
    }
}
