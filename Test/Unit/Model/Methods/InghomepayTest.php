<?php

namespace Mollie\Payment\Test\Unit\Model\Methods;

use Mollie\Payment\Model\Methods\Inghomepay;
use Mollie\Payment\Test\Unit\Model\Methods\AbstractMethodTest;

class InghomepayTest extends AbstractMethodTest
{
    protected $instance = Inghomepay::class;

    protected $code = 'inghomepay';
}
