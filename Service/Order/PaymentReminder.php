<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Order;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mollie\Payment\Api\Data\PendingPaymentReminderInterface;
use Mollie\Payment\Api\Data\SentPaymentReminderInterface;
use Mollie\Payment\Api\Data\SentPaymentReminderInterfaceFactory;
use Mollie\Payment\Api\PendingPaymentReminderRepositoryInterface;
use Mollie\Payment\Api\SentPaymentReminderRepositoryInterface;

class PaymentReminder
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var SentPaymentReminderInterfaceFactory
     */
    private $sentPaymentReminderFactory;

    /**
     * @var SentPaymentReminderRepositoryInterface
     */
    private $sentPaymentReminderRepository;

    /**
     * @var PendingPaymentReminderRepositoryInterface
     */
    private $pendingPaymentReminderRepository;

    /**
     * @var SecondChanceEmail
     */
    private $secondChanceEmail;

    /**
     * @var DeletePaymentReminder
     */
    private $deletePaymentReminder;

    public function __construct(
        ResourceConnection $resourceConnection,
        OrderRepositoryInterface $orderRepository,
        SentPaymentReminderInterfaceFactory $sentPaymentReminderFactory,
        SentPaymentReminderRepositoryInterface $sentPaymentReminderRepository,
        PendingPaymentReminderRepositoryInterface $pendingPaymentReminderRepository,
        SecondChanceEmail $secondChanceEmail,
        DeletePaymentReminder $deletePaymentReminder
    ) {
        $this->orderRepository = $orderRepository;
        $this->sentPaymentReminderFactory = $sentPaymentReminderFactory;
        $this->sentPaymentReminderRepository = $sentPaymentReminderRepository;
        $this->pendingPaymentReminderRepository = $pendingPaymentReminderRepository;
        $this->secondChanceEmail = $secondChanceEmail;
        $this->resourceConnection = $resourceConnection;
        $this->deletePaymentReminder = $deletePaymentReminder;
    }

    public function send(PendingPaymentReminderInterface $pendingPaymentReminder): OrderInterface
    {
        $connection = $this->resourceConnection->getConnection();
        $connection->beginTransaction();

        try {
            $order = $this->orderRepository->get($pendingPaymentReminder->getOrderId());
            $this->moveReminderFromPendingToSent($order, $pendingPaymentReminder);

            $this->secondChanceEmail->send($order);

            $connection->commit();

            return $order;
        } catch (\Exception $exception) {
            $connection->rollBack();

            throw $exception;
        }
    }

    private function moveReminderFromPendingToSent(OrderInterface $order, PendingPaymentReminderInterface $pendingPaymentReminder): void
    {
        try {
            /** @var SentPaymentReminderInterface $sent */
            $sent = $this->sentPaymentReminderFactory->create();
            $sent->setOrderId($pendingPaymentReminder->getOrderId());

            $this->sentPaymentReminderRepository->save($sent);
        } catch (CouldNotSaveException $exception) {
            // It might already exist
        }

        $this->deletePaymentReminder->byEmail($order->getCustomerEmail());
    }
}