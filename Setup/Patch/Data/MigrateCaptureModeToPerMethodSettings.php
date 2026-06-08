<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Setup\Patch\Data;

use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Migrates the v2 global capture flag (payment/mollie_general/enable_manual_capture) to the v3
 * per-method setting (payment/mollie_methods_{method}/capture_mode). Without this patch, merchants
 * who had manual capture enabled globally in v2 will silently revert to automatic capture after
 * upgrading to v3 because the old flag is no longer read.
 *
 * Only writes per-method settings where no explicit value already exists in core_config_data,
 * so merchants who configured per-method settings during a v3 beta are not overwritten.
 */
class MigrateCaptureModeToPerMethodSettings implements DataPatchInterface
{
    private const OLD_PATH = 'payment/mollie_general/enable_manual_capture';
    private const NEW_PATH_PATTERN = 'payment/mollie_methods_%s/capture_mode';

    private const METHODS = [
        'billie',
        'creditcard',
        'klarna',
        'mobilepay',
        'vipps',
    ];

    public function __construct(
        private CollectionFactory $collectionFactory,
        private WriterInterface $configWriter,
    ) {}

    public function apply(): void
    {
        $oldRows = $this->collectionFactory->create()
            ->addFieldToFilter('path', self::OLD_PATH)
            ->getItems();

        if (empty($oldRows)) {
            return;
        }

        $existingPaths = $this->loadExistingPerMethodPaths();

        foreach ($oldRows as $row) {
            $this->migrateScope(
                $row->getData('scope'),
                (int) $row->getData('scope_id'),
                $row->getData('value') ? 'manual' : 'automatic',
                $existingPaths,
            );
        }
    }

    private function migrateScope(string $scope, int $scopeId, string $captureMode, array $existingPaths): void
    {
        $unconfiguredMethods = array_filter(
            self::METHODS,
            fn(string $method) => !isset($existingPaths[$scope][$scopeId][sprintf(self::NEW_PATH_PATTERN, $method)])
        );

        foreach ($unconfiguredMethods as $method) {
            $this->configWriter->save(sprintf(self::NEW_PATH_PATTERN, $method), $captureMode, $scope, $scopeId);
        }
    }

    private function loadExistingPerMethodPaths(): array
    {
        $paths = array_map(
            fn(string $method) => sprintf(self::NEW_PATH_PATTERN, $method),
            self::METHODS
        );

        $rows = $this->collectionFactory->create()
            ->addFieldToFilter('path', ['in' => $paths])
            ->getItems();

        $index = [];
        foreach ($rows as $row) {
            $index[$row->getData('scope')][(int) $row->getData('scope_id')][$row->getData('path')] = true;
        }

        return $index;
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
