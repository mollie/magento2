<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Observer\SalesModelServiceQuoteSubmitSuccess;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Model\Methods\Pointofsale;
use Mollie\Payment\Model\Mollie;

class StartTransactionForPointOfSaleOrders implements ObserverInterface
{
    /**
     * @var Mollie
     */
    private $mollie;
    /**
     * @var State
     */
    private $state;

    public function __construct(
        Mollie $mollie,
        State $state
    ) {
        $this->mollie = $mollie;
        $this->state = $state;
    }

    public function execute(Observer $observer)
    {
        if (!$observer->hasData('order')) {
            return;
        }


        /** @var OrderInterface $order */
        $order = $observer->getData('order');

        $area = $this->state->getAreaCode();
        if ($order->getPayment()->getData('method') != Pointofsale::CODE ||
            $area != Area::AREA_ADMINHTML
        ) {
            return;
        }

        $this->mollie->startTransaction($order);
    }
}
