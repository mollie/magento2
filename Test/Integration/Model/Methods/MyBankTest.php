<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 *  * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Mollie\Payment\Model\Methods\MyBank;

class MyBankTest extends AbstractMethodTest
{
    protected $instance = MyBank::class;

    protected $code = 'mybank';
}
