<?php

namespace Mollie\Payment\Test\Unit\Model\Methods;

use Mollie\Payment\Model\Methods\Ideal;
use Mollie\Payment\Test\Unit\Model\Methods\AbstractMethodTest;

class IdealTest extends AbstractMethodTest
{
    protected $instance = Ideal::class;

    protected $code = 'ideal';
}
