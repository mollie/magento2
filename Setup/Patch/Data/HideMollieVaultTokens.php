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
 * Version 2.x stored a saved card as a Magento vault token. Version 3.x removed the vault integration, so no renderer
 * for these tokens is left. Magento does not filter the customer card list on the payment method, thus each leftover
 * token adds an empty row to "Stored Payment Methods" in the customer account until it expires.
 *
 * These rows are hidden and not deleted, for three reasons:
 *
 * 1. The gateway_token of each row is the Mollie mandate ID. Saved cards in 3.x read those same mandates through the
 *    Mollie Customers API, thus the mandate must stay valid.
 * 2. A delete cascades into vault_payment_token_order_payment_link and drops the link to historical order payments.
 * 3. Hiding is reversible. A merchant who returns to 2.x can set is_visible back to 1 and keeps every saved card. A
 *    delete cannot be undone.
 */
class HideMollieVaultTokens implements DataPatchInterface
{
    private const VAULT_PAYMENT_METHOD_CODES = [
        'mollie_methods_creditcard',
        'mollie_methods_creditcard_vault',
    ];

    public function __construct(
        private ResourceConnection $resourceConnection
    ) {}

    public function apply()
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName('vault_payment_token');

        if (!$connection->isTableExists($tableName)) {
            return $this;
        }

        $connection->update(
            $tableName,
            ['is_visible' => 0],
            ['payment_method_code IN (?)' => self::VAULT_PAYMENT_METHOD_CODES]
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
