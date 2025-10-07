<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Mollie\Payment\Model\Methods\Mobilepay;

class MobilepayMethodTest extends AbstractTestMethod
{
    protected $instance = Mobilepay::class;

    protected $code = 'mobilepay';
}
