<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Setup;

use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory as ConfigReaderFactory;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Sales\Setup\SalesSetupFactory;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Mollie\Payment\Config as MollieConfig;

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
     * @var MollieConfig
     */
    private $mollieConfig;

    /**
     * @var Config\Data\Collection
     */
    private $configReaderFactory;

    /**
     * UpgradeData constructor.
     *
     * @param SalesSetupFactory $salesSetupFactory
     * @param ResourceConnection $resourceConnection
     * @param Config $resourceConfig
     * @param WriterInterface $configWriter
     * @param StoreManagerInterface $storeManager
     * @param MollieConfig $mollieConfig
     * @param ConfigReaderFactory $configReaderFactory
     */
    public function __construct(
        SalesSetupFactory $salesSetupFactory,
        ResourceConnection $resourceConnection,
        Config $resourceConfig,
        WriterInterface $configWriter,
        StoreManagerInterface $storeManager,
        MollieConfig $mollieConfig,
        ConfigReaderFactory $configReaderFactory
    ) {
        $this->salesSetupFactory = $salesSetupFactory;
        $this->resourceConnection = $resourceConnection;
        $this->resourceConfig = $resourceConfig;
        $this->configWriter = $configWriter;
        $this->storeManager = $storeManager;
        $this->mollieConfig = $mollieConfig;
        $this->configReaderFactory = $configReaderFactory;
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

        if (version_compare($context->getVersion(), '1.6.2', '<')) {
            $this->addIndexes($setup);
        }

        if (version_compare($context->getVersion(), '1.13.1', '<')) {
            $this->updateCustomerReturnUrl();
        }

        if (version_compare($context->getVersion(), '1.17.0', '<')) {
            $this->renameMealVoucherToVoucher();
        }

        if (version_compare($context->getVersion(), '1.18.0', '<')) {
            $this->changeSecondChanceEmailTemplatePath();
        }

        // This should run every time
        $this->upgradeActiveState();

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
            'payment/mollie_methods_mybank/active',
        ];

        foreach ($paths as $path) {
            $this->setCorrectWebsiteDefault($path);
        }
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

    private function addIndexes(ModuleDataSetupInterface $setup)
    {
        $setup->getConnection()->addIndex(
            $setup->getTable('sales_order'),
            $this->resourceConnection->getIdxName('sales_order', ['mollie_transaction_id']),
            ['mollie_transaction_id']
        );

        $setup->getConnection()->addIndex(
            $setup->getTable('sales_shipment'),
            $this->resourceConnection->getIdxName('sales_shipment', ['mollie_shipment_id']),
            ['mollie_shipment_id']
        );
    }

    /**
     * The return url is changed. Before this params where added by default, but now you can now add placeholders in
     * the url which will be replaced. That's why we append this variables to the url by default when the url is set.
     */
    private function updateCustomerReturnUrl()
    {
        $collection = $this->configReaderFactory->create()->addFieldToFilter('path', [
            'eq' => 'payment/mollie_general/custom_redirect_url'
        ]);

        foreach ($collection as $configItem) {
            $this->updateCustomerReturnUrlForScope(
                $configItem->getData('scope'),
                $configItem->getData('scope_id'),
                $configItem->getData('value')
            );
        }
    }

    private function updateCustomerReturnUrlForScope(string $scope, int $scopeId, string $currentValue = null)
    {
        $append = '?order_id={{ORDER_ID}}&payment_token={{PAYMENT_TOKEN}}&utm_nooverride=1';

        // The value already contains the new string so don't append it twice.
        if (!$currentValue || strpos($currentValue, $append) !== false) {
            return;
        }

        $newValue = $currentValue . $append;

        $this->configWriter->save(
            'payment/mollie_general/custom_redirect_url',
            $newValue,
            $scope,
            $scopeId
        );
    }

    private function renameMealVoucherToVoucher()
    {
        $collection = $this->configReaderFactory->create()->addFieldToFilter('path', [
            'eq' => 'payment/mollie_methods_mealvoucher/category'
        ]);

        $replacements = [
            'food_and_drinks' => 'meal',
            'home_and_garden' => 'eco',
            'gifts_and_flowers' => 'gift',
        ];

        foreach ($collection as $item) {
            $value = str_replace(
                array_keys($replacements),
                array_values($replacements),
                $item->getData('value')
            );

            $this->configWriter->save(
                'payment/mollie_methods_mealvoucher/category',
                $value,
                $item->getData('scope'),
                $item->getData('scope_id')
            );
        }

        $paths = [
            'payment/mollie_methods_%s/active',
            'payment/mollie_methods_%s/title',
            'payment/mollie_methods_%s/payment_description',
            'payment/mollie_methods_%s/category',
            'payment/mollie_methods_%s/custom_attribute',
            'payment/mollie_methods_%s/days_before_expire',
            'payment/mollie_methods_%s/allowspecific',
            'payment/mollie_methods_%s/specificcountry',
            'payment/mollie_methods_%s/min_order_total',
            'payment/mollie_methods_%s/max_order_total',
            'payment/mollie_methods_%s/payment_surcharge_type',
            'payment/mollie_methods_%s/payment_surcharge_fixed_amount',
            'payment/mollie_methods_%s/payment_surcharge_percentage',
            'payment/mollie_methods_%s/payment_surcharge_limit',
            'payment/mollie_methods_%s/payment_surcharge_tax_class',
            'payment/mollie_methods_%s/sort_order',
        ];

        foreach ($paths as $path) {
            $this->changeConfigPath(
                sprintf($path, 'mealvoucher'),
                sprintf($path, 'voucher')
            );
        }
    }

    private function changeConfigPath($oldPath, $newPath)
    {
        $collection = $this->configReaderFactory->create()->addFieldToFilter('path', [
            'eq' => $oldPath,
        ]);

        foreach ($collection as $item) {
            $item->setData('path', $newPath);
            $item->save();
        }
    }

    private function changeSecondChanceEmailTemplatePath()
    {
        $collection = $this->configReaderFactory->create()->addFieldToFilter('path', [
            'eq' => 'payment/mollie_general/second_chance_email_template'
        ]);

        foreach ($collection as $item) {
            if (stripos($item->getData('value'), 'mollie_general_second_chance_email_template') === false) {
                return;
            }

            $this->configWriter->save(
                'payment/mollie_general/second_chance_email_template',
                'mollie_second_chance_email_second_chance_email_second_chance_email_template',
                $item->getData('scope'),
                $item->getData('scope_id')
            );
        }
    }
}
