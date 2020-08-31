<?php

namespace Mollie\Payment\Test\Unit\Model\Methods;

use Mollie\Payment\Model\Methods\Eps;
use Mollie\Payment\Test\Unit\Model\Methods\AbstractMethodTest;

class EpsTest extends AbstractMethodTest
{
    protected $instance = Eps::class;

    protected $code = 'eps';
}
