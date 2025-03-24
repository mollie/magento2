<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Setup\Patch\Data;

use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory as ConfigReaderFactory;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class UpdateCustomerReturnUrl implements DataPatchInterface
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

    /**
     * The return url is changed. Before this params where added by default, but now you can now add placeholders in
     * the url which will be replaced. That's why we append this variables to the url by default when the url is set.
     */
    public function apply()
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

        return $this;
    }

    private function updateCustomerReturnUrlForScope(string $scope, int $scopeId, ?string $currentValue = null)
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

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}
