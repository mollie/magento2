<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Mollie\Payment\Model\Methods\Pointofsale;

class PointofsaleMethodTest extends AbstractTestMethod
{
    protected $instance = Pointofsale::class;

    protected $code = 'pointofsale';
}
