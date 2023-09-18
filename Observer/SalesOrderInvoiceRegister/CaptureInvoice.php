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
use Mollie\Payment\Config;
use Mollie\Payment\Model\Client\Payments\CapturePayment;

class CaptureInvoice implements ObserverInterface
{
    /**
     * @var Config
     */
    private $config;
    /**
     * @var CapturePayment
     */
    private $capturePayment;

    public function __construct(
        Config $config,
        CapturePayment $capturePayment
    ) {
        $this->capturePayment = $capturePayment;
        $this->config = $config;
    }

    public function execute(Observer $observer)
    {
        /** @var OrderInterface $order */
        $order = $observer->getData('order');
        $transactionId = $order->getMollieTransactionId() ?? '';
        $useOrdersApi = substr($transactionId, 0, 4) == 'ord_';
        if ($useOrdersApi || !$this->config->useManualCapture((int)$order->getStoreId())) {
            return;
        }

        /** @var InvoiceInterface $invoice */
        $invoice = $observer->getData('invoice');

        $this->capturePayment->execute($invoice);
    }
}
