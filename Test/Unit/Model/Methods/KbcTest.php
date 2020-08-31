<?php

namespace Mollie\Payment\Test\Unit\Model\Methods;

use Mollie\Payment\Model\Methods\Kbc;
use Mollie\Payment\Test\Unit\Model\Methods\AbstractMethodTest;

class KbcTest extends AbstractMethodTest
{
    protected $instance = Kbc::class;

    protected $code = 'kbc';
}
