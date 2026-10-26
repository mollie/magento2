<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Setup\Patch\Data;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Earlier versions of this module could let the webhook add the authorization transaction again on a payment that already had a
 * transaction. Magento 2.4.8 then links that transaction to itself as its own parent. Closing such a transaction
 * closes its parent again and again, which hangs the process that handles a refund or cancellation of that order.
 *
 * A transaction can never be its own parent, so the parent link is removed for every Mollie transaction where it
 * points to itself.
 */
class UnlinkSelfReferencingPaymentTransactions implements DataPatchInterface
{
    private const MOLLIE_PAYMENT_METHOD_PATTERN = 'mollie_%';

    public function __construct(
        private ResourceConnection $resourceConnection,
    ) {}

    public function apply()
    {
        $connection = $this->resourceConnection->getConnection('sales');

        $molliePaymentIds = $connection->select()
            ->from($this->resourceConnection->getTableName('sales_order_payment'), ['entity_id'])
            ->where('method LIKE ?', self::MOLLIE_PAYMENT_METHOD_PATTERN);

        $connection->update(
            $this->resourceConnection->getTableName('sales_payment_transaction'),
            ['parent_id' => null],
            [
                'parent_id = transaction_id',
                'payment_id IN (?)' => $molliePaymentIds,
            ],
        );

        return $this;
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}
