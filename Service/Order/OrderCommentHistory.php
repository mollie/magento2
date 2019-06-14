<?php

namespace Mollie\Payment\Service\Order;

use Magento\Framework\Phrase;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderStatusHistoryRepositoryInterface;
use Magento\Sales\Model\Order\Status\HistoryFactory;

class OrderCommentHistory
{
    /**
     * @var HistoryFactory
     */
    private $historyFactory;

    /**
     * @var OrderStatusHistoryRepositoryInterface
     */
    private $historyRepository;

    public function __construct(
        HistoryFactory $historyFactory,
        OrderStatusHistoryRepositoryInterface $historyRepository
    ) {
        $this->historyFactory = $historyFactory;
        $this->historyRepository = $historyRepository;
    }

    public function add(OrderInterface $order, Phrase $message)
    {
        if (!$message->getText()) {
            return;
        }
        /** @var \Magento\Sales\Api\Data\OrderStatusHistoryInterface $history */
        $history = $this->historyFactory->create();
        $history->setParentId($order->getEntityId())
            ->setComment($message)
            ->setEntityName('order');
        $this->historyRepository->save($history);
    }
}