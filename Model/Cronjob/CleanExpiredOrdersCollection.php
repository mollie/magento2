<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model\Cronjob;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactory;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Helper;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Mollie\Payment\Service\Mollie\AsyncPaymentMethods;
use Psr\Log\LoggerInterface;

class CleanExpiredOrdersCollection extends Collection
{
    /**
     * @param string[] $excludedMethodCodes
     */
    public function __construct(
        EntityFactory $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        Snapshot $entitySnapshot,
        Helper $coreResourceHelper,
        private readonly AsyncPaymentMethods $asyncPaymentMethods,
        private readonly array $excludedMethodCodes = [],
        ?AdapterInterface $connection = null,
        ?AbstractDb $resource = null,
    ) {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $entitySnapshot,
            $coreResourceHelper,
            $connection,
            $resource,
        );
    }

    /**
     * Do not auto cancel pending asynchronous orders. They may take a few days before they receive an update.
     * Payment link orders are canceled by the Mollie module when the link expires.
     *
     * @return string[]
     */
    public function getAllIds($limit = null, $offset = null): array
    {
        $codes = [...$this->asyncPaymentMethods->allCodes(), ...$this->excludedMethodCodes];
        if ($codes === []) {
            return parent::getAllIds($limit, $offset);
        }

        $this->getSelect()
            ->join(
                ['payment' => $this->getTable('sales_order_payment')],
                'main_table.entity_id = payment.parent_id',
                [],
            )
            ->where('payment.method NOT IN (?)', $codes);

        return parent::getAllIds($limit, $offset);
    }
}
