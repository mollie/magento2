<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Observer\SalesModelServiceQuoteSubmitBefore;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;

class CopyPaymentFeeToOrder implements ObserverInterface
{
    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        /* @var OrderInterface $order */
        $order = $observer->getEvent()->getData('order');

        /* @var CartInterface $quote */
        $quote = $observer->getEvent()->getData('quote');

        if (!$order || !$quote) {
            return;
        }

        $extensionAttributes = $quote->getExtensionAttributes();

        if (!$extensionAttributes) {
            return;
        }

        $order->setMolliePaymentFee($extensionAttributes->getMolliePaymentFee());
        $order->setMolliePaymentFeeTax($extensionAttributes->getMolliePaymentFeeTax());

        $order->setBaseMolliePaymentFee($extensionAttributes->getBaseMolliePaymentFee());
        $order->setBaseMolliePaymentFeeTax($extensionAttributes->getBaseMolliePaymentFeeTax());
    }
}
