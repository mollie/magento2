<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 *  * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Mollie\Payment\Model\Methods\Creditcard;

class CreditcardTest extends AbstractMethodTest
{
    protected $instance = Creditcard::class;

    protected $code = 'creditcard';
}
