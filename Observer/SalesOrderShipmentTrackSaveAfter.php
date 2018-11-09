<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Mollie\Payment\Model\Mollie as MollieModel;
use Mollie\Payment\Helper\General as MollieHelper;

/**
 * Class SalesOrderShipmentTrackSaveAfter
 *
 * @package Mollie\Payment\Observer
 */
class SalesOrderShipmentTrackSaveAfter implements ObserverInterface
{

    /**
     * @var MollieModel
     */
    private $mollieModel;
    /**
     * @var MollieHelper
     */
    private $mollieHelper;

    /**
     * SalesOrderShipmentTrackSaveAfter constructor.
     *
     * @param MollieModel  $mollieModel
     * @param MollieHelper $mollieHelper
     */
    public function __construct(
        MollieModel $mollieModel,
        MollieHelper $mollieHelper
    ) {
        $this->mollieModel = $mollieModel;
        $this->mollieHelper = $mollieHelper;
    }

    /**
     * @param Observer $observer
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order\Shipment\Track $track */
        $track = $observer->getEvent()->getTrack();

        /** @var \Magento\Sales\Model\Order\Shipment $shipment */
        $shipment = $track->getShipment();

        /** @var \Magento\Sales\Model\Order $order */
        $order = $shipment->getOrder();

        if ($this->mollieHelper->isPaidUsingMollieOrdersApi($order)) {
            $this->mollieModel->updateShipmentTrack($shipment, $track, $order);
        }
    }
}
