<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Mollie\Payment\Model\Methods\Twint;

class TwintMethodTest extends AbstractTestMethod
{
    protected $instance = Twint::class;

    protected $code = 'twint';
}
