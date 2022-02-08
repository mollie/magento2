<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Setup\Patch\Data;

use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory as ConfigReaderFactory;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class ChangeSecondChanceEmailTemplatePath implements DataPatchInterface
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
