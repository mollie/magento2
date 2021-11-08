<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Observer\CheckoutSubmitAllAfter;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\InstantPurchase\Model\QuoteManagement\PaymentConfiguration;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Model\Methods\CreditcardVault;
use Mollie\Payment\Model\Mollie;

class StartTransactionForInstantPurchaseOrders implements ObserverInterface
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
        /** @var OrderInterface $order */
            $order = $observer->getData('order');

        $payment = $order->getPayment();
        $instantPurchase = $payment->getAdditionalInformation(PaymentConfiguration::MARKER);
        if (!$instantPurchase || $instantPurchase != 'true') {
            return;
        }

        $method = $payment->getMethodInstance();
        if (!$method instanceof CreditcardVault) {
            return;
        }

        $this->mollie->startTransaction($order);
    }
}
