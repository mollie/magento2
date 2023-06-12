<?php
/*
 *  Copyright Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Mollie\Payment\Observer\SalesModelServiceQuoteSubmitSuccess;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Model\Methods\Paymentlink;
use Mollie\Payment\Model\Mollie;

class StartTransactionForPaymentLinkOrders implements ObserverInterface
{
    /**
     * @var Mollie
     */
    private $mollie;

    public function __construct(
        Mollie $mollie
    ) {
        $this->mollie = $mollie;
    }

    public function execute(Observer $observer)
    {
        if (!$observer->hasData('order')) {
            return;
        }

        /** @var OrderInterface $order */
        $order = $observer->getData('order');

        if ($order->getPayment()->getData('method') != Paymentlink::CODE) {
            return;
        }

        $this->mollie->startTransaction($order);
    }
}
