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
use Mollie\Payment\Model\Mollie as MollieModel;
use Mollie\Payment\Helper\General as MollieHelper;

/**
 * Class SalesOrderShipmentSaveBefore
 *
 * @package Mollie\Payment\Observer
 */
class CreateMollieShipment implements ObserverInterface
{
    /**
     * @var MollieHelper
     */
    private $mollieHelper;

    /**
     * @var Orders
     */
    private $ordersApi;

    /**
     * @var CapturePayment
     */
    private $capturePayment;

    public function __construct(
        MollieHelper $mollieHelper,
        Orders $ordersApi,
        CapturePayment $capturePayment
    ) {
        $this->mollieHelper = $mollieHelper;
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
        $useOrdersApi = preg_match('/^ord_\w+$/', $transactionId);
        if ($useOrdersApi) {
            $this->ordersApi->createShipment($shipment, $order);
        }

        if (!$useOrdersApi) {
            $this->capturePayment->execute($shipment, $order);
        }
    }
}
