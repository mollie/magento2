<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\MessageQueue\Consumer\ConfigInterface as ConsumerConfigInterface;

class AreQueuesConfiguredCorrectly
{
    public function __construct(
        private DeploymentConfig $deploymentConfig,
        private ConsumerConfigInterface $consumerConfig
    ) {}

    public function execute(): bool
    {
        $consumers = [];
        $consumerConfig = $this->consumerConfig->getConsumers();
        foreach ($consumerConfig as $consumer) {
            $consumers[] = $consumer->getName();
        }

        if (!in_array('mollie.transaction.processor', $consumers)) {
            return false;
        }

        $allowedConsumers = $this->deploymentConfig->get('cron_consumers_runner/consumers', []);
        if ($allowedConsumers == []) {
            return true;
        }

        return in_array('mollie.transaction.processor', $allowedConsumers);
    }
}
