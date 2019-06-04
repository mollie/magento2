<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Setup;

use Magento\Sales\Setup\SalesSetupFactory;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Class UpgradeData
 *
 * @package Mollie\Payment\Setup
 */
class UpgradeData implements UpgradeDataInterface
{
    /**
     * Sales setup factory
     *
     * @var SalesSetupFactory
     */
    private $salesSetupFactory;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var Config
     */
    private $resourceConfig;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * UpgradeData constructor.
     *
     * @param SalesSetupFactory $salesSetupFactory
     * @param ResourceConnection $resourceConnection
     * @param Config $resourceConfig
     * @param WriterInterface $configWriter
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        SalesSetupFactory $salesSetupFactory,
        ResourceConnection $resourceConnection,
        Config $resourceConfig,
        WriterInterface $configWriter,
        StoreManagerInterface $storeManager
    ) {
        $this->salesSetupFactory = $salesSetupFactory;
        $this->resourceConnection = $resourceConnection;
        $this->resourceConfig = $resourceConfig;
        $this->configWriter = $configWriter;
        $this->storeManager = $storeManager;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface   $context
     */
    public function upgrade(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.4.0', '<')) {
            $this->createMollieShipmentId($setup);
        }

        if (version_compare($context->getVersion(), '1.6.0', '<')) {
            $this->removeBitcoinConfiguration();
        }

        if (version_compare($context->getVersion(), '1.6.1', '<')) {
            $this->upgradeActiveState();
        }

        $setup->endSetup();
    }

    /**
     * @param $setup
     */
    public function createMollieShipmentId($setup)
    {
        /** @var \Magento\Sales\Setup\SalesSetup $salesSetup */
        $salesSetup = $this->salesSetupFactory->create(['setup' => $setup]);

        /**
         * Add 'mollie_shipment_id' attributes for order
         */
        $options = ['type' => 'varchar', 'visible' => false, 'required' => false];
        $salesSetup->addAttribute('shipment', 'mollie_shipment_id', $options);
    }

    /**
     * See https://github.com/magento/magento2/issues/22231
     * This is part of a fix that sets the default of the active config to 1 instead of 0. That's why we extract the
     * current value from the database. If that is not 1 we set the value in the database to 0. This is to prevent
     * that we turn on payment methods that are not active.
     */
    private function upgradeActiveState()
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
        ];

        foreach ($paths as $path) {
            $this->setCorrectWebsiteDefault($path);
        }
    }

    private function setCorrectWebsiteDefault($path)
    {
        $connection = $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('core_config_data');

        $query = 'select value from ' . $tableName . ' where scope = "default" and path = :path';
        $result = $connection->fetchOne($query, ['path' => $path]);

        if ($result !== false) {
            return;
        }

        $this->resourceConfig->saveConfig($path, '0', 'default', 0);
    }

    private function removeBitcoinConfiguration()
    {
        $paths = [
            'payment/mollie_methods_bitcoin/active',
            'payment/mollie_methods_bitcoin/title',
            'payment/mollie_methods_bitcoin/method',
            'payment/mollie_methods_bitcoin/payment_description',
            'payment/mollie_methods_bitcoin/allowspecific',
            'payment/mollie_methods_bitcoin/specificcountry',
            'payment/mollie_methods_bitcoin/min_order_total',
            'payment/mollie_methods_bitcoin/max_order_total',
            'payment/mollie_methods_bitcoin/sort_order',
        ];

        foreach ($this->storeManager->getStores() as $store) {
            foreach ($paths as $path) {
                $this->configWriter->delete($path, ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
                $this->configWriter->delete($path, 'stores', $store->getId());
                $this->configWriter->delete($path, 'websites', $store->getId());
            }
        }
    }
}
