<?php

namespace Mollie\Payment\Observer\SalesModelServiceQuoteSubmitBefore;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;

class CopyPaymentFeeToOrder implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        /* @var OrderInterface $order */
        $order = $observer->getEvent()->getData('order');

        /* @var CartInterface $quote */
        $quote = $observer->getEvent()->getData('quote');

        $quoteExtension = $quote->getExtensionAttributes();

        $order->setMolliePaymentFee($quoteExtension->getMolliePaymentFee());
        $order->setMolliePaymentFeeTax($quoteExtension->getMolliePaymentFeeTax());

        $order->setBaseMolliePaymentFee($quoteExtension->getBaseMolliePaymentFee());
        $order->setBaseMolliePaymentFeeTax($quoteExtension->getBaseMolliePaymentFeeTax());
    }
}
