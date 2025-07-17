<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Observer\BackendBlockWidgetGridPrepareGridBefore;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\ResourceModel\Order\Payment\Transaction\Collection;

class AddAdditionalInformationToCollection implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $collection = $observer->getEvent()->getCollection();

        if (!$collection instanceof Collection) {
            return;
        }

        $collection->addPaymentInformation(['sop.additional_information']);
    }
}
