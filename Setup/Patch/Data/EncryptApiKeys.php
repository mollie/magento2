<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Setup\Patch\Data;

use Magento\Config\Model\ResourceModel\Config;
use Magento\Config\Model\ResourceModel\Config\Data\Collection;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class EncryptApiKeys implements DataPatchInterface
{
    /**
     * @var Collection
     */
    private $configReaderFactory;

    /**
     * @var Config
     */
    private $configResource;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    public function __construct(
        CollectionFactory $configReaderFactory,
        Config $configResource,
        EncryptorInterface $encryptor
    ) {
        $this->configReaderFactory = $configReaderFactory;
        $this->configResource = $configResource;
        $this->encryptor = $encryptor;
    }

    public function apply()
    {
        $this->updatePath('payment/mollie_general/apikey_live');
        $this->updatePath('payment/mollie_general/apikey_test');

        return $this;
    }

    private function updatePath(string $path): void
    {
        $collection = $this->configReaderFactory->create()->addFieldToFilter('path', [
            'eq' => $path,
        ]);

        foreach ($collection as $configItem) {
            $this->updateRecord($configItem);
        }
    }

    private function updateRecord(Value $configItem): void
    {
        $value = (string)$configItem->getData('value');

        // Same check as in \Magento\Config\Model\Config\Backend\Encrypted::beforeSave
        if (!preg_match('/^\*+$/', $value) && !empty($value)) {
            $this->configResource->saveConfig(
                $configItem->getData('path'),
                $this->encryptor->encrypt($value),
                $configItem->getData('scope'),
                $configItem->getData('scope_id')
            );
        }
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
