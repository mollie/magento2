<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Mollie\Payment\Model\Methods\Payconiq;

class PayconiqTest extends AbstractMethodTest
{
    protected $instance = Payconiq::class;

    protected $code = 'payconiq';
}
