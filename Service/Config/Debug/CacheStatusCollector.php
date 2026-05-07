<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Config\Debug;

use Magento\Framework\App\Cache\TypeListInterface;

class CacheStatusCollector implements CollectorInterface
{
    public function __construct(
        private readonly TypeListInterface $cacheTypeList,
    ) {
    }

    /**
     * Builds the cache-status.txt body listing each cache type and its state.
     */
    public function collect(): array
    {
        return ['cache-status.txt' => $this->render()];
    }

    public function getReadmeDescription(): string
    {
        return "- cache-status.txt\n"
            . "  Each Magento cache type and whether it is enabled or disabled.";
    }

    public function render(): string
    {
        $rows = [];
        foreach ($this->cacheTypeList->getTypes() as $type) {
            $id = (string)$type->getData('id');
            $enabled = (int)$type->getData('status') === 1;
            $rows[$id] = $enabled ? 'enabled' : 'disabled';
        }

        ksort($rows, SORT_STRING);

        $lines = [];
        foreach ($rows as $id => $state) {
            $lines[] = $id . ': ' . $state;
        }

        if ($lines === []) {
            return "No cache types declared.\n";
        }

        return implode("\n", $lines) . "\n";
    }
}
