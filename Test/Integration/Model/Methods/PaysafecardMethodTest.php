<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Mollie\Payment\Model\Methods\Paysafecard;

class PaysafecardMethodTest extends AbstractTestMethod
{
    protected $instance = Paysafecard::class;

    protected $code = 'paysafecard';
}
