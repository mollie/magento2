<?php

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Mollie\Payment\Model\Methods\Twint;

class TwintTest extends AbstractMethodTest
{
    protected $instance = Twint::class;

    protected $code = 'twint';
}
