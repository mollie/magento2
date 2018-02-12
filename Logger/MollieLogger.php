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
            $this->addInfo($type . ': ' . print_r($data, true));
        } elseif (is_object($data)) {
            $this->addInfo($type . ': ' . print_r($data, true));
        } else {
            $this->addInfo($type . ': ' . $data);
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
            $this->addError($type . ': ' . print_r($data, true));
        } elseif (is_object($data)) {
            $this->addError($type . ': ' . print_r($data));
        } else {
            $this->addError($type . ': ' . $data);
        }
    }
}
