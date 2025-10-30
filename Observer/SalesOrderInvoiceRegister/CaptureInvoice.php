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
use Mollie\Payment\Model\Client\Payments\CapturePayment;
use Mollie\Payment\Service\Mollie\Order\CanUseManualCapture;

class CaptureInvoice implements ObserverInterface
{
    public function __construct(
        private readonly CapturePayment $capturePayment,
        private readonly CanUseManualCapture $canUseManualCapture,
    ) {
    }

    public function execute(Observer $observer): void
    {
        /** @var OrderInterface $order */
        $order = $observer->getData('order');
        if (!$this->canUseManualCapture->execute($order)) {
            return;
        }

        /** @var InvoiceInterface $invoice */
        $invoice = $observer->getData('invoice');

        $this->capturePayment->execute($invoice);
    }
}
