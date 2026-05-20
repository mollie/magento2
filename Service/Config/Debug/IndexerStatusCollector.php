<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Config\Debug;

use Magento\Framework\Indexer\IndexerInterface;
use Magento\Framework\Indexer\StateInterface;
use Magento\Indexer\Model\Indexer\CollectionFactory;

class IndexerStatusCollector implements CollectorInterface
{
    public function __construct(
        private readonly CollectionFactory $indexerCollectionFactory,
    ) {
    }

    /**
     * Builds the indexer-status.txt body listing each indexer, its status and mode.
     */
    public function collect(): array
    {
        return ['indexer-status.txt' => $this->render()];
    }

    public function getReadmeDescription(): string
    {
        return "- indexer-status.txt\n"
            . "  Each Magento indexer with its current status (Ready / Reindex required /\n"
            . "  Processing / Suspended) and mode (realtime or schedule).";
    }

    public function render(): string
    {
        $rows = [];
        /** @var IndexerInterface $indexer */
        foreach ($this->indexerCollectionFactory->create()->getItems() as $indexer) {
            $id = (string)$indexer->getId();
            $rows[$id] = [
                'status' => $this->describeStatus((string)$indexer->getStatus()),
                'mode' => $indexer->isScheduled() ? 'schedule' : 'realtime',
            ];
        }

        ksort($rows, SORT_STRING);

        $lines = [];
        foreach ($rows as $id => $row) {
            $lines[] = $id . ': status=' . $row['status'] . ', mode=' . $row['mode'];
        }

        if ($lines === []) {
            return "No indexers declared.\n";
        }

        return implode("\n", $lines) . "\n";
    }

    private function describeStatus(string $status): string
    {
        return match ($status) {
            StateInterface::STATUS_VALID => 'Ready',
            StateInterface::STATUS_INVALID => 'Reindex required',
            StateInterface::STATUS_WORKING => 'Processing',
            'suspended' => 'Suspended', // StateInterface::STATUS_SUSPENDED added after 2.4.6
            default => $status !== '' ? $status : 'unknown',
        };
    }
}
