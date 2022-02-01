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

class RemoveInghomepayConfiguration implements DataPatchInterface
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
            'payment/mollie_methods_inghomepay/active',
            'payment/mollie_methods_inghomepay/title',
            'payment/mollie_methods_inghomepay/method',
            'payment/mollie_methods_inghomepay/payment_description',
            'payment/mollie_methods_inghomepay/days_before_expire',
            'payment/mollie_methods_inghomepay/allowspecific',
            'payment/mollie_methods_inghomepay/specificcountry',
            'payment/mollie_methods_inghomepay/min_order_total',
            'payment/mollie_methods_inghomepay/max_order_total',
            'payment/mollie_methods_inghomepay/payment_surcharge_type',
            'payment/mollie_methods_inghomepay/payment_surcharge_fixed_amount',
            'payment/mollie_methods_inghomepay/payment_surcharge_percentage',
            'payment/mollie_methods_inghomepay/payment_surcharge_limit',
            'payment/mollie_methods_inghomepay/payment_surcharge_tax_class',
            'payment/mollie_methods_inghomepay/sort_order',
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

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}
