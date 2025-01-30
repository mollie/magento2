<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Mollie\Payment\Model\Methods\MbWay;

class MbWayTest extends AbstractMethodTest
{
    protected $instance = MbWay::class;

    protected $code = 'mbway';
}
