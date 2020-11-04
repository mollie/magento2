<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Mollie\Payment\Observer\MollieStartTransaction;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Api\Data\PendingPaymentReminderInterface;
use Mollie\Payment\Api\Data\PendingPaymentReminderInterfaceFactory;
use Mollie\Payment\Api\PendingPaymentReminderRepositoryInterface;
use Mollie\Payment\Config;

class SavePendingOrder implements ObserverInterface
{
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

    public function __construct(
        Config $config,
        PendingPaymentReminderInterfaceFactory $reminderFactory,
        PendingPaymentReminderRepositoryInterface $repository
    ) {
        $this->reminderFactory = $reminderFactory;
        $this->repository = $repository;
        $this->config = $config;
    }

    public function execute(Observer $observer)
    {
        /** @var OrderInterface $order */
        $order = $observer->getData('order');

        if (!$this->config->automaticallySendSecondChanceEmails($order->getStoreId())) {
            return;
        }

        /** @var PendingPaymentReminderInterface $reminder */
        $reminder = $this->reminderFactory->create();
        $reminder->setOrderId($order->getEntityId());

        $this->repository->save($reminder);
    }
}