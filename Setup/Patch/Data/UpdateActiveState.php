<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Setup\Patch\Data;

use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchInterface;

class UpdateActiveState implements DataPatchInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var Config
     */
    private $resourceConfig;

    public function __construct(
        ResourceConnection $resourceConnection,
        Config $resourceConfig
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->resourceConfig = $resourceConfig;
    }

    /**
     * See https://github.com/magento/magento2/issues/22231
     * This is part of a fix that sets the default of the active config to 1 instead of 0. That's why we extract the
     * current value from the database. If that is not 1 we set the value in the database to 0. This is to prevent
     * that we turn on payment methods that are not active.
     */
    public function apply()
    {
        $paths = [
            'payment/mollie_methods_bancontact/active',
            'payment/mollie_methods_banktransfer/active',
            'payment/mollie_methods_belfius/active',
            'payment/mollie_methods_bitcoin/active',
            'payment/mollie_methods_creditcard/active',
            'payment/mollie_methods_ideal/active',
            'payment/mollie_methods_kbc/active',
            'payment/mollie_methods_paypal/active',
            'payment/mollie_methods_paysafecard/active',
            'payment/mollie_methods_sofort/active',
            'payment/mollie_methods_inghomepay/active',
            'payment/mollie_methods_giropay/active',
            'payment/mollie_methods_eps/active',
            'payment/mollie_methods_klarnapaylater/active',
            'payment/mollie_methods_klarnasliceit/active',
            'payment/mollie_methods_paymentlink/active',
            'payment/mollie_methods_giftcard/active',
            'payment/mollie_methods_przelewy24/active',
            'payment/mollie_methods_mybank/active',
        ];

        foreach ($paths as $path) {
            $this->setCorrectWebsiteDefault($path);
        }

        return $this;
    }

    private function setCorrectWebsiteDefault($path)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('core_config_data');

        $query = 'select value from ' . $tableName . ' where scope = "default" and path = :path';
        $result = $connection->fetchOne($query, ['path' => $path]);

        if ($result !== false) {
            return;
        }

        $this->resourceConfig->saveConfig($path, '0', 'default', 0);
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
