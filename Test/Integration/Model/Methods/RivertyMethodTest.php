<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Mollie\Payment\Model\Methods\Riverty;

class RivertyMethodTest extends AbstractTestMethod
{
    protected $instance = Riverty::class;

    protected $code = 'riverty';
}
