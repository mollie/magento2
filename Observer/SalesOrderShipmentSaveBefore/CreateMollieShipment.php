<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Observer\SalesOrderShipmentSaveBefore;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Mollie\Payment\Model\Client\Orders;
use Mollie\Payment\Model\Client\Payments\CapturePayment;

/**
 * Class SalesOrderShipmentSaveBefore
 *
 * @package Mollie\Payment\Observer
 */
class CreateMollieShipment implements ObserverInterface
{
    /**
     * @var \Mollie\Payment\Config
     */
    private $config;
    /**
     * @var Orders
     */
    private $ordersApi;
    /**
     * @var CapturePayment
     */
    private $capturePayment;

    public function __construct(
        \Mollie\Payment\Config $config,
        Orders $ordersApi,
        CapturePayment $capturePayment
    ) {
        $this->config = $config;
        $this->ordersApi = $ordersApi;
        $this->capturePayment = $capturePayment;
    }

    /**
     * @param Observer $observer
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order\Shipment $shipment */
        $shipment = $observer->getEvent()->getShipment();

        /** @var \Magento\Sales\Model\Order $order */
        $order = $shipment->getOrder();

        $transactionId = $order->getMollieTransactionId() ?? '';
        $useOrdersApi = substr($transactionId, 0, 4) == 'ord_';
        if ($useOrdersApi) {
            $this->ordersApi->createShipment($shipment, $order);
        }
    }
}
