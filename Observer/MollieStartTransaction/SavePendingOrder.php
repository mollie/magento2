<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Observer\MollieStartTransaction;

use Exception;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Api\Data\PendingPaymentReminderInterface;
use Mollie\Payment\Api\Data\PendingPaymentReminderInterfaceFactory;
use Mollie\Payment\Api\PendingPaymentReminderRepositoryInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Helper\General;
use Mollie\Payment\Model\Methods\Banktransfer;

class SavePendingOrder implements ObserverInterface
{
    public function __construct(
        private General $mollieHelper,
        private Config $config,
        private PendingPaymentReminderInterfaceFactory $reminderFactory,
        private PendingPaymentReminderRepositoryInterface $repository,
        private EncryptorInterface $encryptor
    ) {}

    public function execute(Observer $observer): void
    {
        /** @var OrderInterface $order */
        $order = $observer->getData('order');

        if (
            !$this->config->automaticallySendSecondChanceEmails(storeId($order->getStoreId())) ||
            $order->getPayment()->getMethod() == Banktransfer::CODE
        ) {
            return;
        }

        try {
            // If this succeeds there already exists a reminder.
            $this->repository->getByOrderId($order->getEntityId());

            return;
        } catch (NoSuchEntityException $exception) {
            // Ignore.
        }

        try {
            /** @var PendingPaymentReminderInterface $reminder */
            $reminder = $this->reminderFactory->create();
            $reminder->setOrderId($order->getEntityId());

            if ($order->getCustomerId()) {
                $reminder->setCustomerId($order->getCustomerId());
            }

            if (!$order->getCustomerId() && $order->getCustomerEmail()) {
                $reminder->setHash($this->encryptor->hash($order->getCustomerEmail()));
            }

            $this->repository->save($reminder);
        } catch (Exception $exception) {
            $message = 'Got an exception while trying to save a payment reminder: ' . $exception->getMessage();
            $message .= ' - Store ID: ' . storeId($order->getStoreId());
            $this->mollieHelper->addTolog('error', $message);
        }
    }
}
