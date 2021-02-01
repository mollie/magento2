<?php

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Mollie\Payment\Model\Methods\Giropay;

class GiropayTest extends AbstractMethodTest
{
    protected $instance = Giropay::class;

    protected $code = 'giropay';
}
