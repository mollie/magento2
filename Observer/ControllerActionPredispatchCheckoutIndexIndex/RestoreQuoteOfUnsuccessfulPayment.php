<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Mollie\Payment\Observer\ControllerActionPredispatchCheckoutIndexIndex;

use Magento\Checkout\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Mollie;

class RestoreQuoteOfUnsuccessfulPayment implements ObserverInterface
{
    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var Config
     */
    private $config;

    public function __construct(
        Session $checkoutSession,
        Config $config
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->config = $config;
    }

    public function execute(Observer $observer)
    {
        /** @var OrderInterface $order */
        $order = $this->checkoutSession->getLastRealOrder();
        $payment = $order->getPayment();
        if (!$payment || !$payment->getMethodInstance() instanceof Mollie) {
            return;
        }

        if ($order->getState() === Order::STATE_NEW &&
            $order->getStatus() === $this->config->orderStatusPending($order->getStoreId())
        ) {
            $this->checkoutSession->restoreQuote();
        }
    }
}