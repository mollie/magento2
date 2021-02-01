<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 *  * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Mollie\Payment\Model\Methods\Paypal;

class PaypalTest extends AbstractMethodTest
{
    protected $instance = Paypal::class;

    protected $code = 'paypal';
}
