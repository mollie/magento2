<?php

namespace Mollie\Payment\Model\Methods;

use Mollie\Payment\Tests\Unit\Model\Methods\AbstractMethodTest;

class SofortTest extends AbstractMethodTest
{
    protected $instance = Sofort::class;

    protected $code = 'sofort';
}
