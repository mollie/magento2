<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Mollie\Payment\Api\Data\PendingPaymentReminderInterface;
use Mollie\Payment\Api\Data\SentPaymentReminderInterface;
use Mollie\Payment\Api\Data\SentPaymentReminderInterfaceFactory;
use Mollie\Payment\Api\PendingPaymentReminderRepositoryInterface;
use Mollie\Payment\Api\SentPaymentReminderRepositoryInterface;
use Mollie\Payment\Logger\MollieLogger;

class PaymentReminder
{
    public function __construct(
        private MollieLogger $logger,
        private OrderRepositoryInterface $orderRepository,
        private SentPaymentReminderInterfaceFactory $sentPaymentReminderFactory,
        private SentPaymentReminderRepositoryInterface $sentPaymentReminderRepository,
        private PendingPaymentReminderRepositoryInterface $pendingPaymentReminderRepository,
        private SecondChanceEmail $secondChanceEmail,
        private DeletePaymentReminder $deletePaymentReminder,
    ) {}

    public function send(PendingPaymentReminderInterface $pendingPaymentReminder): OrderInterface
    {
        $order = $this->orderRepository->get($pendingPaymentReminder->getOrderId());

        if (in_array($order->getState(), [Order::STATE_PROCESSING, Order::STATE_COMPLETE])) {
            $this->logger->addInfoLog(
                'info',
                sprintf('Order #%s is already completed, not sending payment reminder', $order->getIncrementId()),
            );

            $this->pendingPaymentReminderRepository->delete($pendingPaymentReminder);

            return $order;
        }

        if (!$this->orderIsInStock($order)) {
            $this->logger->addInfoLog(
                'info',
                sprintf(
                    'On or more products from order #%s are not stock, not sending payment reminder',
                    $order->getIncrementId(),
                ),
            );

            $this->pendingPaymentReminderRepository->delete($pendingPaymentReminder);

            return $order;
        }

        $this->logger->addInfoLog(
            'info',
            sprintf('Preparing to send the payment reminder for order #%s', $order->getIncrementId()),
        );

        $this->moveReminderFromPendingToSent($order, $pendingPaymentReminder);

        $this->logger->addInfoLog(
            'info',
            sprintf('Payment reminder record moved to sent table for order #%s', $order->getIncrementId()),
        );

        $this->secondChanceEmail->send($order);

        return $order;
    }

    private function moveReminderFromPendingToSent(OrderInterface $order, PendingPaymentReminderInterface $pendingPaymentReminder): void
    {
        if ($this->isAlreadySent($order)) {
            $this->pendingPaymentReminderRepository->delete($pendingPaymentReminder);
            $this->deleteRemindersByOrder($order);
            return;
        }

        /** @var SentPaymentReminderInterface $sent */
        $sent = $this->sentPaymentReminderFactory->create();
        $sent->setOrderId($pendingPaymentReminder->getOrderId());
        $this->sentPaymentReminderRepository->save($sent);

        $this->deleteRemindersByOrder($order);
    }

    private function deleteRemindersByOrder(OrderInterface $order): void
    {
        if ($customerId = $order->getCustomerId()) {
            $this->deletePaymentReminder->deleteByCustomerId((int)$customerId);
            return;
        }
        $this->deletePaymentReminder->deleteByEmail((string)$order->getCustomerEmail());
    }

    private function isAlreadySent(OrderInterface $order): bool
    {
        try {
            $this->sentPaymentReminderRepository->getByOrderId((int)$order->getEntityId());
            return true;
        } catch (NoSuchEntityException $exception) {
            return false;
        }
    }

    private function orderIsInStock(OrderInterface $order): bool
    {
        /** @var OrderItemInterface $item */
        foreach ($order->getAllVisibleItems() as $item) {
            if (!$item->getProduct() || !$item->getProduct()->isSaleable()) {
                return false;
            }
        }

        return true;
    }
}
