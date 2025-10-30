<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Observer\SalesModelServiceQuoteSubmitSuccess;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Model\Methods\Pointofsale;
use Mollie\Payment\Service\Mollie\StartTransaction;

class StartTransactionForPointOfSaleOrders implements ObserverInterface
{
    public function __construct(
        private State $state,
        private StartTransaction $startTransaction
    ) {}

    public function execute(Observer $observer): void
    {
        if (!$observer->hasData('order')) {
            return;
        }

        /** @var OrderInterface $order */
        $order = $observer->getData('order');

        $area = $this->state->getAreaCode();
        if (
            $order->getPayment()->getData('method') != Pointofsale::CODE ||
            $area != Area::AREA_ADMINHTML
        ) {
            return;
        }

        $this->startTransaction->execute($order);
    }
}
