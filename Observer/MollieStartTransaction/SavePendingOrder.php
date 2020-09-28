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

class SavePendingOrder implements ObserverInterface
{
    /**
     * @var PendingPaymentReminderInterfaceFactory
     */
    private $reminderFactory;

    /**
     * @var PendingPaymentReminderRepositoryInterface
     */
    private $repository;

    public function __construct(
        PendingPaymentReminderInterfaceFactory $reminderFactory,
        PendingPaymentReminderRepositoryInterface $repository
    ) {
        $this->reminderFactory = $reminderFactory;
        $this->repository = $repository;
    }

    public function execute(Observer $observer)
    {
        /** @var OrderInterface $order */
        $order = $observer->getData('order');

        /** @var PendingPaymentReminderInterface $reminder */
        $reminder = $this->reminderFactory->create();
        $reminder->setOrderId($order->getEntityId());

        try {
            $this->repository->save($reminder);
        } catch (\Exception $exception) {
            // TODO: Log exception
            return;
        }
    }
}