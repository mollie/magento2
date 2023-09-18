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
use Mollie\Payment\Service\Mollie\Order\CanUseManualCapture;
use Mollie\Payment\Service\Mollie\Order\UsedMollieApi;

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
    /**
     * @var CanUseManualCapture
     */
    private $canUseManualCapture;
    /**
     * @var UsedMollieApi
     */
    private $usedMollieApi;

    public function __construct(
        Config $config,
        CapturePayment $capturePayment,
        CanUseManualCapture $canUseManualCapture,
        UsedMollieApi $usedMollieApi
    ) {
        $this->capturePayment = $capturePayment;
        $this->config = $config;
        $this->canUseManualCapture = $canUseManualCapture;
        $this->usedMollieApi = $usedMollieApi;
    }

    public function execute(Observer $observer)
    {
        /** @var OrderInterface $order */
        $order = $observer->getData('order');
        if ($this->usedMollieApi->execute($order) == UsedMollieApi::TYPE_ORDERS ||
            !$this->canUseManualCapture->execute($order)
        ) {
            return;
        }

        /** @var InvoiceInterface $invoice */
        $invoice = $observer->getData('invoice');

        $this->capturePayment->execute($invoice);
    }
}
