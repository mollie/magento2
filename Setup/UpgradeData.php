<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Setup;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Sales\Setup\SalesSetupFactory;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Store\Model\StoreManagerInterface;

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
     * @param WriterInterface $configWriter
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        SalesSetupFactory $salesSetupFactory,
        WriterInterface $configWriter,
        StoreManagerInterface $storeManager
    ) {
        $this->salesSetupFactory = $salesSetupFactory;
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
