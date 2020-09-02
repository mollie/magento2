<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Model\PaymentFee\Invoice\Total;

use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Model\PaymentFee\Invoice\Total\PaymentFee;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class PaymentFeeTest extends IntegrationTestCase
{
    public function testAddsTheFeeCorrect()
    {
        /** @var PaymentFee $instance */
        $instance = $this->objectManager->create(PaymentFee::class);

        /** @var InvoiceInterface $invoice */
        $invoice = $this->objectManager->create(InvoiceInterface::class);

        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);
        $order->setData('mollie_payment_fee', 1.61);
        $order->setData('base_mollie_payment_fee', 1.61);
        $order->setData('mollie_payment_fee_tax', 0.34);
        $order->setData('base_mollie_payment_fee_tax', 0.34);

        $invoice->setOrder($order);

        $instance->collect($invoice);

        $this->assertEquals(1.61, $invoice->getData('mollie_payment_fee'));
        $this->assertEquals(1.61, $invoice->getData('base_mollie_payment_fee'));
        $this->assertEquals(0.34, $invoice->getData('mollie_payment_fee_tax'));
        $this->assertEquals(0.34, $invoice->getData('base_mollie_payment_fee_tax'));

        $this->assertEquals(1.61, $invoice->getGrandTotal());
        $this->assertEquals(1.61, $invoice->getBaseGrandTotal());
        $this->assertEquals(0.34, $invoice->getTaxAmount());
        $this->assertEquals(0.34, $invoice->getBaseTaxAmount());
    }
}
