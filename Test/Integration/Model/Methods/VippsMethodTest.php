<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Mollie\Payment\Model\Methods\Vipps;

class VippsMethodTest extends AbstractTestMethod
{
    protected $instance = Vipps::class;

    protected $code = 'vipps';
}
