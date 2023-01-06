<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Mollie\Payment\Observer\ControllerActionPredispatchCheckoutIndexIndex;

use Magento\Checkout\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
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

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    public function __construct(
        Session $checkoutSession,
        TimezoneInterface $timezone,
        Config $config
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->config = $config;
        $this->timezone = $timezone;
    }

    public function execute(Observer $observer)
    {
        /** @var OrderInterface $order */
        $order = $this->checkoutSession->getLastRealOrder();
        $payment = $order->getPayment();
        if (!$payment || !$payment->getMethodInstance() instanceof Mollie) {
            return;
        }

        $createdAt = $this->timezone->date(new \DateTime($order->getCreatedAt()));
        $now = $this->timezone->date();
        $diff = $now->diff($createdAt);
        if ($diff->i > 5) {
            return;
        }

        if ($order->getState() === Order::STATE_PENDING_PAYMENT &&
            $order->getStatus() === $this->config->orderStatusPending($order->getStoreId())
        ) {
            $this->checkoutSession->restoreQuote();
            $this->config->addToLog('info', 'Restored quote of order ' . $order->getIncrementId());
        }
    }
}
