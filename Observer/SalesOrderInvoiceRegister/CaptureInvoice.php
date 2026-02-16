<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Observer\SalesOrderInvoiceRegister;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Model\Client\Payments\CapturePaymentForInvoice;
use Mollie\Payment\Service\Mollie\Order\CanUseManualCapture;
use Mollie\Payment\Service\Mollie\Order\WhenToCapture;

class CaptureInvoice implements ObserverInterface
{
    public function __construct(
        private readonly CapturePaymentForInvoice $capturePayment,
        private readonly CanUseManualCapture $canUseManualCapture,
        private readonly WhenToCapture $whenToCapture,
    ) {
    }

    public function execute(Observer $observer): void
    {
        /** @var OrderInterface $order */
        $order = $observer->getData('order');
        if (!$this->canUseManualCapture->execute($order)) {
            return;
        }

        $method = $order->getPayment()->getMethod();
        if (!$this->whenToCapture->onInvoice($method, storeId($order->getStoreId()))) {
            return;
        }

        /** @var InvoiceInterface $invoice */
        $invoice = $observer->getData('invoice');

        $this->capturePayment->execute($invoice);
    }
}
