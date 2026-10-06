<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order;

use DateInterval;
use DateTimeImmutable;
use Magento\Framework\Stdlib\DateTime;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Mollie\Payment\Model\Methods\Paymentlink;
use Mollie\Payment\Service\Mollie\Order\IsPaymentLinkExpired;

class ExpiredPaymentLinkOrders
{
    private const BATCH_SIZE = 50;
    private const LOOKBACK_PERIOD = 'P7D';

    public function __construct(
        private readonly CollectionFactory $collectionFactory,
        private readonly IsPaymentLinkExpired $isPaymentLinkExpired,
    ) {}

    /**
     * @return OrderInterface[]
     */
    public function getForStore(int $storeId): array
    {
        $latestCreationDate = $this->isPaymentLinkExpired->getLatestExpiredCreationDate($storeId);

        $collection = $this->collectionFactory->create();
        $collection->getSelect()
            ->join(
                ['payment' => $collection->getTable('sales_order_payment')],
                'main_table.entity_id = payment.parent_id',
                [],
            )
            ->where('payment.method = ?', Paymentlink::CODE);

        $collection->addFieldToFilter('main_table.store_id', $storeId)
            ->addFieldToFilter('main_table.state', ['in' => Paymentlink::OPEN_ORDER_STATES])
            ->addFieldToFilter('main_table.created_at', ['lteq' => $this->format($latestCreationDate)])
            ->addFieldToFilter('main_table.created_at', ['gt' => $this->getEarliestCreationDate($latestCreationDate)])
            ->setOrder('main_table.created_at', 'DESC')
            ->setPageSize(self::BATCH_SIZE);

        /** @var OrderInterface[] $orders */
        $orders = $collection->getItems();

        return array_values($orders);
    }

    private function getEarliestCreationDate(DateTimeImmutable $latestCreationDate): string
    {
        return $this->format($latestCreationDate->sub(new DateInterval(self::LOOKBACK_PERIOD)));
    }

    private function format(DateTimeImmutable $date): string
    {
        return $date->format(DateTime::DATETIME_PHP_FORMAT);
    }
}
