<?php

namespace Mollie\Payment\Setup\Patch\Data;

use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory as ConfigReaderFactory;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class RenameIdealToIdealWero implements DataPatchInterface
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

    public function apply(): void
    {
        $collection = $this->configReaderFactory->create()->addFieldToFilter('path', [
            'eq' => 'payment/mollie_methods_ideal/title'
        ]);

        foreach ($collection as $configItem) {
            $this->updateMethodTitle(
                $configItem->getData('scope'),
                $configItem->getData('scope_id'),
                $configItem->getData('value')
            );
        }
    }

    private function updateMethodTitle(string $scope, int $scopeId, ?string $currentValue = null)
    {
        // Some merchant might have iDeal + €1 or something similar, don't update those
        if (strtolower($currentValue) !== 'ideal') {
            return;
        }

        $newValue = $currentValue . ' | Wero';

        $this->configWriter->save(
            'payment/mollie_methods_ideal/title',
            $newValue,
            $scope,
            $scopeId
        );
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
