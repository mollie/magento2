<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mollie\Payment\Cron;

use DateInterval;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Mollie as MollieModel;
use Mollie\Payment\Service\Order\OrderCommentHistory;

class ProcessPendingOrders
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var MollieModel
     */
    private $mollieModel;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var OrderCommentHistory
     */
    private $orderCommentHistory;

    public function __construct(
        Config $config,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OrderRepositoryInterface $orderRepository,
        DateTime $dateTime,
        StoreManagerInterface $storeManager,
        MollieModel $mollieModel,
        OrderCommentHistory $orderCommentHistory
    ) {
        $this->config = $config;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderRepository = $orderRepository;
        $this->dateTime = $dateTime;
        $this->mollieModel = $mollieModel;
        $this->storeManager = $storeManager;
        $this->orderCommentHistory = $orderCommentHistory;
    }

    public function execute(): void
    {
        $stores = $this->storeManager->getStores();
        foreach ($stores as $store) {
            $this->processOrdersForStore($store);
        }
    }

    private function processOrdersForStore(StoreInterface $store)
    {
        if (!$this->config->isPendingOrderCronEnabled((int)$store->getId())) {
            return;
        }

        $pendingOrders = $this->getPendingOrders($store);
        foreach ($pendingOrders as $order) {
            try {
                $this->processOrder($order);
            } catch (\Throwable $exception) {
                $this->config->addToLog('error', [
                    'message' => 'Error processing pending order in cron',
                    'order_id' => $order->getEntityId(),
                    'error' => $exception->getMessage(),
                    'trace' => $exception->getTraceAsString()
                ]);
            }
        }
    }

    /**
     * @return OrderInterface[]
     */
    private function getPendingOrders(StoreInterface $store): array
    {
        $fromDate = (new \DateTimeImmutable($this->dateTime->gmtDate()))
            ->sub(new DateInterval('P10D'))
            ->format('Y-m-d H:i:s');
        $toDate = (new \DateTimeImmutable($this->dateTime->gmtDate()))
            ->sub(new DateInterval('PT30M'))
            ->format('Y-m-d H:i:s');

        $batchSize = $this->config->pendingOrderCronBatchSize((int)$store->getId());

        $this->searchCriteriaBuilder
            ->addFilter('state', Order::STATE_PENDING_PAYMENT)
            ->addFilter('created_at', $fromDate, 'gt')
            ->addFilter('created_at', $toDate, 'lt')
            ->addFilter('mollie_transaction_id', null, 'notnull')
            ->addFilter('store_id', $store->getId())
            ->setPageSize($batchSize);

        return $this->orderRepository->getList($this->searchCriteriaBuilder->create())->getItems();
    }

    private function processOrder(OrderInterface $order): void
    {
        $this->config->addToLog('Calling processTransaction from pending orders cronjob', [
            'order_id' => $order->getEntityId(),
        ]);

        $initialState = $order->getState();
        $result = $this->mollieModel->processTransaction($order->getEntityId(), 'webhook');

        $newOrder = $this->orderRepository->get($order->getEntityId());
        if ($newOrder->getState() !== $initialState) {
            $this->orderCommentHistory->add(
                $newOrder,
                __('This order got updated through cron. Please check if your webhooks are reachable for Mollie.'),
                false,
            );
        }

        if (is_array($result) && isset($result['error']) && $result['error']) {
            throw new \Exception($result['msg'] ?? 'Unknown error during transaction processing');
        }
    }
}
