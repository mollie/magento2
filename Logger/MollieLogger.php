<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Logger;

use Monolog\Logger;

/**
 * Class MollieLogger
 *
 * @package Mollie\Payment\Logger
 */
class MollieLogger extends Logger
{

    /**
     * Add info data to Mollie Log
     *
     * @param $type
     * @param $data
     */
    public function addInfoLog($type, $data)
    {
        if (is_array($data)) {
            $this->addRecord(static::INFO, $type . ': ' . json_encode($data));
        } elseif (is_object($data)) {
            $this->addRecord(static::INFO, $type . ': ' . json_encode($data));
        } else {
            $this->addRecord(static::INFO, $type . ': ' . $data);
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
        if (is_array($data)) {
            $this->addRecord(static::ERROR, $type . ': ' . json_encode($data));
        } elseif (is_object($data)) {
            $this->addRecord(static::ERROR, $type . ': ' . json_encode($data));
        } else {
            $this->addRecord(static::ERROR, $type . ': ' . $data);
        }
    }
}
