<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Setup\Patch\Data;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\StoreManagerInterface;

class RemoveBitcoinConfiguration implements DataPatchInterface
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    public function __construct(
        StoreManagerInterface $storeManager,
        WriterInterface $configWriter
    ) {
        $this->storeManager = $storeManager;
        $this->configWriter = $configWriter;
    }

    public function apply()
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

        return $this;
    }

    public function getAliases()
    {
        return [];
    }

    public static function getDependencies()
    {
        return [];
    }
}
