<?php

namespace Mollie\Payment\Test\Unit\Model\Methods;

use Mollie\Payment\Model\Methods\MyBank;
use Mollie\Payment\Test\Unit\Model\Methods\AbstractMethodTest;

class MyBankTest extends AbstractMethodTest
{
    protected $instance = MyBank::class;

    protected $code = 'mybank';
}
