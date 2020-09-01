<?php

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Mollie\Payment\Model\Methods\Sofort;

class SofortTest extends AbstractMethodTest
{
    protected $instance = Sofort::class;

    protected $code = 'sofort';
}
