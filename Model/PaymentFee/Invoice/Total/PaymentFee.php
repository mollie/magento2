<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\PaymentFee\Invoice\Total;

use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Invoice\Total\AbstractTotal;

class PaymentFee extends AbstractTotal
{
    /**
     * @param Invoice $invoice
     * @return $this|AbstractTotal
     */
    public function collect(Invoice $invoice)
    {
        $order = $invoice->getOrder();
        $paymentFee = $order->getData('mollie_payment_fee');
        $basePaymentFee = $order->getData('base_mollie_payment_fee');
        $paymentFeeTax = $order->getData('mollie_payment_fee_tax');
        $basePaymentFeeTax = $order->getData('base_mollie_payment_fee_tax');

        $invoice->setData('mollie_payment_fee', $paymentFee);
        $invoice->setData('base_mollie_payment_fee', $basePaymentFee);
        $invoice->setData('mollie_payment_fee_tax', $paymentFeeTax);
        $invoice->setData('base_mollie_payment_fee_tax', $basePaymentFeeTax);

        $invoice->setGrandTotal($invoice->getGrandTotal() + $paymentFee);
        $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $basePaymentFee);
        $invoice->setTaxAmount($invoice->getTaxAmount() + $paymentFeeTax);
        $invoice->setBaseTaxAmount($invoice->getBaseTaxAmount() + $basePaymentFeeTax);

        return $this;
    }
}
