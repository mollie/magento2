<?php

namespace Mollie\Payment\Model\Methods;

use Mollie\Payment\Test\Unit\Model\Methods\AbstractMethodTest;

class MyBankTest extends AbstractMethodTest
{
    protected $instance = MyBank::class;

    protected $code = 'mybank';
}
