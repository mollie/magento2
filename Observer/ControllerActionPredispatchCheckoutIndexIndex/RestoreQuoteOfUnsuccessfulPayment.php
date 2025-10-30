<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Observer\ControllerActionPredispatchCheckoutIndexIndex;

use Magento\Checkout\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Mollie;

class RestoreQuoteOfUnsuccessfulPayment implements ObserverInterface
{
    public function __construct(
        private Session $checkoutSession,
        private Config $config
    ) {}

    public function execute(Observer $observer): void
    {
        /** @var OrderInterface $order */
        $order = $this->checkoutSession->getLastRealOrder();
        $payment = $order->getPayment();
        if (!$payment || !$payment->getMethodInstance() instanceof Mollie) {
            return;
        }

        $mollieSucces = $payment->getAdditionalInformation('mollie_success');
        if ($mollieSucces === null || $mollieSucces === true) {
            return;
        }

        $this->checkoutSession->restoreQuote();
        $this->config->addToLog('info', 'Restored quote of order ' . $order->getIncrementId());
    }
}
