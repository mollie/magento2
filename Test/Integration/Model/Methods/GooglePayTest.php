<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Mollie\Payment\Model\Methods\GooglePay;

class GooglePayTest extends AbstractMethodTest
{
    protected $instance = GooglePay::class;

    protected $code = 'googlepay';
}
