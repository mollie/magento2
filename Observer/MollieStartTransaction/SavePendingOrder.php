<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Mollie\Payment\Observer\MollieStartTransaction;

use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Api\Data\PendingPaymentReminderInterface;
use Mollie\Payment\Api\Data\PendingPaymentReminderInterfaceFactory;
use Mollie\Payment\Api\PendingPaymentReminderRepositoryInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Helper\General;
use Mollie\Payment\Model\Methods\Banktransfer;

class SavePendingOrder implements ObserverInterface
{
    /**
     * @var General
     */
    private $mollieHelper;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var PendingPaymentReminderInterfaceFactory
     */
    private $reminderFactory;

    /**
     * @var PendingPaymentReminderRepositoryInterface
     */
    private $repository;
    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    public function __construct(
        General $mollieHelper,
        Config $config,
        PendingPaymentReminderInterfaceFactory $reminderFactory,
        PendingPaymentReminderRepositoryInterface $repository,
        EncryptorInterface $encryptor
    ) {
        $this->mollieHelper = $mollieHelper;
        $this->config = $config;
        $this->reminderFactory = $reminderFactory;
        $this->repository = $repository;
        $this->encryptor = $encryptor;
    }

    public function execute(Observer $observer)
    {
        /** @var OrderInterface $order */
        $order = $observer->getData('order');

        if (!$this->config->automaticallySendSecondChanceEmails($order->getStoreId()) ||
            $order->getPayment()->getMethod() == Banktransfer::CODE) {
            return;
        }

        try {
            // If this succeeds there already exists a reminder.
            $this->repository->getByOrderId($order->getEntityId());
            return;
        } catch (\Magento\Framework\Exception\NoSuchEntityException $exception) {
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
        } catch (\Exception $exception) {
            $message = 'Got an exception while trying to save a payment reminder: ' . $exception->getMessage();
            $message .= ' - Store ID: ' . $order->getStoreId();
            $this->mollieHelper->addTolog('error', $message);
        }
    }
}
