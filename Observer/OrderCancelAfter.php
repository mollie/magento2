<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Mollie\Payment\Model\Mollie as MollieModel;
use Mollie\Payment\Helper\General as MollieHelper;

/**
 * Class OrderCancelAfter
 *
 * @package Mollie\Payment\Observer
 */
class OrderCancelAfter implements ObserverInterface
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
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * OrderCancelAfter constructor.
     *
     * @param MollieModel      $mollieModel
     * @param MollieHelper     $mollieHelper
     */
    public function __construct(
        MollieModel $mollieModel,
        MollieHelper $mollieHelper,
        ManagerInterface $messageManager
    ) {
        $this->mollieModel = $mollieModel;
        $this->mollieHelper = $mollieHelper;
        $this->messageManager = $messageManager;
    }

    /**
     * @param Observer $observer
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getorder();

        /**
         * When manually marking an order as paid we don't want to communicate to Mollie as it will throw an exception.
         */
        if ($order->getReordered() || !$this->mollieHelper->isPaidUsingMollieOrdersApi($order)) {
            return;
        }

        try {
            $this->mollieModel->cancelOrder($order);
        } catch (LocalizedException $localizedException) {
            $this->messageManager->addErrorMessage($localizedException->getMessage());
        }
    }
}
