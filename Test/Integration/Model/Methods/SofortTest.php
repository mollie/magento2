<?php

namespace Mollie\Payment\Model\Methods;

use Mollie\Payment\Test\Integration\Model\Methods\AbstractMethodTest;

class SofortTest extends AbstractMethodTest
{
    protected $instance = Sofort::class;

    protected $code = 'sofort';
}
