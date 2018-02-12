<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Logger;

use Monolog\Logger;
use Magento\Framework\Logger\Handler\Base;

/**
 * Class MollieHandler
 *
 * @package Mollie\Payment\Logger
 */
class MollieHandler extends Base
{

    /**
     * @var int
     */
    protected $loggerType = Logger::DEBUG;
    /**
     * @var string
     */
    protected $fileName = '/var/log/mollie.log';
}
