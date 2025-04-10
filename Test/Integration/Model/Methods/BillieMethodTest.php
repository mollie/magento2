<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Mollie\Payment\Model\Methods\Billie;

class BillieMethodTest extends AbstractTestMethod
{
    protected $instance = Billie::class;

    protected $code = 'billie';
}
