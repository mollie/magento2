<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Queue\Publisher;

use Magento\Framework\MessageQueue\PublisherInterface;
use Mollie\Payment\Api\Data\TransactionToProcessInterface;

class PublishTransactionToProcess
{
    /**
     * @var string
     */
    const TOPIC_NAME = 'mollie.transaction.process';

    /**
     * @var PublisherInterface
     */
    protected $publisher;

    /**
     * @param PublisherInterface  $publisher
     */
    public function __construct(
        PublisherInterface $publisher
    ) {
        $this->publisher = $publisher;
    }

    public function publish(TransactionToProcessInterface $data): void
    {
        $this->publisher->publish(self::TOPIC_NAME, $data);
    }
}
