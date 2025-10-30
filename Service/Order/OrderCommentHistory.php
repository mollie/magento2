<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order;

use Magento\Framework\Phrase;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderStatusHistoryInterface;
use Magento\Sales\Api\OrderStatusHistoryRepositoryInterface;
use Magento\Sales\Model\Order\Status\HistoryFactory;

class OrderCommentHistory
{
    public function __construct(
        private HistoryFactory $historyFactory,
        private OrderStatusHistoryRepositoryInterface $historyRepository
    ) {}

    public function add(OrderInterface $order, Phrase $message, bool $isCustomerNotified = false): void
    {
        if (!$message->getText()) {
            return;
        }

        /** @var OrderStatusHistoryInterface $history */
        $history = $this->historyFactory->create();
        $history->setParentId($order->getEntityId())
            ->setComment($message)
            ->setStatus($order->getStatus())
            ->setIsCustomerNotified($isCustomerNotified)
            ->setEntityName('order');

        $this->historyRepository->save($history);
    }
}
