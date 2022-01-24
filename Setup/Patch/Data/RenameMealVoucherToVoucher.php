<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Setup\Patch\Data;

use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory as ConfigReaderFactory;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class RenameMealVoucherToVoucher implements DataPatchInterface
{
    /**
     * @var ConfigReaderFactory
     */
    private $configReaderFactory;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    public function __construct(
        ConfigReaderFactory $configReaderFactory,
        WriterInterface $configWriter
    ) {
        $this->configReaderFactory = $configReaderFactory;
        $this->configWriter = $configWriter;
    }

    public function apply()
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

        return $this;
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

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}
