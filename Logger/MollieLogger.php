<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

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
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var StreamHandler
     */
    private $handler;
    /**
     * @var Logger
     */
    private $instance;

    public function __construct(
        Logger $logger,
        StreamHandler $handler
    ) {
        $this->logger = $logger;
        $this->handler = $handler;
    }

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
    public function addInfoLog($type, $data)
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
    public function addErrorLog($type, $data)
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
