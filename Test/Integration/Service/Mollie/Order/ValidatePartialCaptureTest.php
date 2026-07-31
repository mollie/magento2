<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie\Order;

use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Model\Order\Invoice;
use Mollie\Payment\Exceptions\PartialCaptureNotSupported;
use Mollie\Payment\Service\Mollie\Order\ValidatePartialCapture;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class ValidatePartialCaptureTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testThrowsWhenTheMethodDoesNotSupportPartialCaptures(): void
    {
        $invoice = $this->prepareInvoice('mollie_methods_riverty', 44.0);

        $instance = $this->objectManager->create(ValidatePartialCapture::class);

        $this->expectException(PartialCaptureNotSupported::class);
        $instance->execute($invoice);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testMentionsTheMethodTitleAndHowToContinue(): void
    {
        $invoice = $this->prepareInvoice('mollie_methods_riverty', 44.0);

        $instance = $this->objectManager->create(ValidatePartialCapture::class);

        $this->expectExceptionMessage(
            'Riverty does not support partial captures. Create a single invoice or shipment for the complete order, '
            . 'or cancel the order to release the authorization.'
        );
        $instance->execute($invoice);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testAcceptsAnInvoiceThatCoversTheCompleteOrder(): void
    {
        $invoice = $this->prepareInvoice('mollie_methods_riverty', 100.0);

        $instance = $this->objectManager->create(ValidatePartialCapture::class);
        $instance->execute($invoice);

        $this->addToAssertionCount(1);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testAcceptsAPartialInvoiceForAMethodThatSupportsPartialCaptures(): void
    {
        $invoice = $this->prepareInvoice('mollie_methods_creditcard', 44.0);

        $instance = $this->objectManager->create(ValidatePartialCapture::class);
        $instance->execute($invoice);

        $this->addToAssertionCount(1);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testAcceptsAPartialInvoiceForALegacyOrdersApiTransaction(): void
    {
        $invoice = $this->prepareInvoice('mollie_methods_riverty', 44.0, 100.0, 'ord_dummytransaction');

        $instance = $this->objectManager->create(ValidatePartialCapture::class);
        $instance->execute($invoice);

        $this->addToAssertionCount(1);
    }

    /**
     * The invoice total is accumulated by unrounded float additions in Magento's total collectors, while the order
     * total comes back from a decimal column as a string. Both represent the same amount of money.
     *
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testAcceptsAnInvoiceThatDiffersFromTheOrderInTheLastFloatingPointDigit(): void
    {
        $invoice = $this->prepareInvoice('mollie_methods_riverty', 615.06 + 2.15 + 129.61, '746.8200');

        $instance = $this->objectManager->create(ValidatePartialCapture::class);
        $instance->execute($invoice);

        $this->addToAssertionCount(1);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testStillRefusesAPartialInvoiceOnAnOrderWithShippingAndTax(): void
    {
        $invoice = $this->prepareInvoice('mollie_methods_riverty', 615.06, '746.8200');

        $instance = $this->objectManager->create(ValidatePartialCapture::class);

        $this->expectException(PartialCaptureNotSupported::class);
        $instance->execute($invoice);
    }

    private function prepareInvoice(
        string $method,
        float $invoiceGrandTotal,
        string|float $orderGrandTotal = 100.0,
        string $transactionId = 'tr_dummytransaction',
    ): InvoiceInterface {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod($method);
        $order->setMollieTransactionId($transactionId);
        $order->setBaseGrandTotal($orderGrandTotal);

        /** @var Invoice $invoice */
        $invoice = $this->objectManager->create(Invoice::class);
        $invoice->setOrder($order);
        $invoice->setBaseGrandTotal($invoiceGrandTotal);

        return $invoice;
    }
}
