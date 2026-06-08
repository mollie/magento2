<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Observer\SalesModelServiceQuoteSubmitSuccess;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Model\SavedCardConsentFactory;
use Mollie\Payment\Model\SavedCardConsentRepository;

class SaveConsentData implements ObserverInterface
{
    public function __construct(
        private SavedCardConsentFactory $savedCardConsentFactory,
        private SavedCardConsentRepository $savedCardConsentRepository,
    ) {}

    public function execute(Observer $observer): void
    {
        /** @var OrderInterface $order */
        $order = $observer->getData('order');
        $payment = $order->getPayment();

        if (!$payment) {
            return;
        }

        $info = $payment->getAdditionalInformation();

        if (!array_key_exists('mollie_save_card', $info) || !$info['mollie_save_card']) {
            return;
        }

        $orderId = (int)$order->getEntityId();
        if (!$orderId) {
            return;
        }

        $consent = $this->savedCardConsentFactory->create();
        $consent->setOrderId($orderId);
        $consent->setStoreId(storeId($order->getStoreId()));
        $consent->setConsentTimestamp((new \DateTime())->format('Y-m-d H:i:s'));

        $this->savedCardConsentRepository->save($consent);
    }
}
