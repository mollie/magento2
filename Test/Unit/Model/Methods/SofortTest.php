<?php

namespace Mollie\Payment\Test\Unit\Model\Methods;

use Mollie\Payment\Model\Methods\Sofort;
use Mollie\Payment\Test\Unit\Model\Methods\AbstractMethodTest;

class SofortTest extends AbstractMethodTest
{
    protected $instance = Sofort::class;

    protected $code = 'sofort';
}
