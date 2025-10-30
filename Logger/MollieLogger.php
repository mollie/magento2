<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Logger;

use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;

/**
 * Class MollieLogger
 *
 * @package Mollie\Payment\Logger
 */
class MollieLogger
{
    private ?Logger $instance = null;

    public function __construct(
        private Logger $logger,
        private StreamHandler $handler
    ) {}

    private function getLogger(): Logger
    {
        if ($this->instance) {
            return $this->instance;
        }

        $this->instance = $this->logger;
        $this->instance->pushHandler($this->handler);

        return $this->instance;
    }

    /**
     * Add info data to Mollie Log
     *
     * @param $type
     * @param $data
     */
    public function addInfoLog(string $type, $data): void
    {
        // The level class doesn't exist in older monolog versions, which are used by older Magento versions
        $level = class_exists(Level::class) ? Level::Info : 200;

        if (is_array($data)) {
            $this->getLogger()->addRecord($level, $type . ': ' . json_encode($data));
        } elseif (is_object($data)) {
            $this->getLogger()->addRecord($level, $type . ': ' . json_encode($data));
        } else {
            $this->getLogger()->addRecord($level, $type . ': ' . $data);
        }
    }

    /**
     * Add error data to mollie Log
     *
     * @param $type
     * @param $data
     */
    public function addErrorLog(string $type, $data): void
    {
        // The level class doesn't exist in older monolog versions, which are used by older Magento versions
        $level = class_exists(Level::class) ? Level::Error : 400;

        if (is_array($data)) {
            $this->getLogger()->addRecord($level, $type . ': ' . json_encode($data));
        } elseif (is_object($data)) {
            $this->getLogger()->addRecord($level, $type . ': ' . json_encode($data));
        } else {
            $this->getLogger()->addRecord($level, $type . ': ' . $data);
        }
    }
}
