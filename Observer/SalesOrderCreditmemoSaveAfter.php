<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Mollie\Payment\Model\Mollie as MollieModel;
use Mollie\Payment\Helper\General as MollieHelper;

/**
 * Class SalesOrderCreditmemoSaveAfter
 *
 * @package Mollie\Payment\Observer
 */
class SalesOrderCreditmemoSaveAfter implements ObserverInterface
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
     * SalesOrderCreditmemoAfter constructor.
     *
     * @param MollieModel      $mollieModel
     * @param MollieHelper     $mollieHelper
     * @param ManagerInterface $messageManager
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
        /** @var \Magento\Sales\Model\Order\Creditmemo $creditmemo */
        $creditmemo = $observer->getEvent()->getCreditmemo();

        /** @var \Magento\Sales\Model\Order $order */
        $order = $creditmemo->getOrder();

        if ($this->mollieHelper->isPaidUsingMollieOrdersApi($order)) {
            try {
                $this->mollieModel->createOrderRefund($creditmemo, $order);
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                throw new LocalizedException(
                    __('Mollie API: %1', $e->getMessage())
                );
            }
        }
    }
}
