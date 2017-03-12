<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Logger;

use Monolog\Logger;
use Magento\Framework\Logger\Handler\Base;

class MollieHandler extends Base
{

    protected $loggerType = Logger::DEBUG;
    protected $fileName = '/var/log/mollie.log';
}
