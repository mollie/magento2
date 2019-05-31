<?php

namespace Mollie\Payment\Model\Methods;

use Mollie\Payment\Tests\Unit\Model\Methods\AbstractMethodTest;

class IdealTest extends AbstractMethodTest
{
    protected $instance = Ideal::class;

    protected $code = 'ideal';
}
