<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Mollie\Payment\Model\Methods\Bancomatpay;

class BancomatpayTest extends AbstractMethodTest
{
    protected $instance = Bancomatpay::class;

    protected $code = 'bancomatpay';
}
