<?php

namespace Mollie\Payment\Test\Unit\Model\Methods;

use Mollie\Payment\Model\Methods\Banktransfer;
use Mollie\Payment\Test\Unit\Model\Methods\AbstractMethodTest;

class BanktransferTest extends AbstractMethodTest
{
    protected $instance = Banktransfer::class;

    protected $code = 'banktransfer';
}
