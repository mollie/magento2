<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Observer\CheckoutSubmitAllAfter;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Module\Manager;
use Magento\InstantPurchase\Model\QuoteManagement\PaymentConfiguration;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Model\Methods\CreditcardVault;
use Mollie\Payment\Service\Mollie\StartTransaction;

class StartTransactionForInstantPurchaseOrders implements ObserverInterface
{
    private ?string $redirectUrl = null;

    public function __construct(
        private Manager $moduleManager,
        private StartTransaction $startTransaction
    ) {}

    public function getRedirectUrl(): ?string
    {
        return $this->redirectUrl;
    }

    public function execute(Observer $observer): void
    {
        if (!$this->moduleManager->isEnabled('Magento_InstantPurchase')) {
            return;
        }

        if (!$observer->hasData('order')) {
            return;
        }

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

        $this->redirectUrl = $this->startTransaction->execute($order);
    }
}
