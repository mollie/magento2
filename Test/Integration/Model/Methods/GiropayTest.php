<?php

namespace Mollie\Payment\Model\Methods;

use Mollie\Payment\Test\Integration\Model\Methods\AbstractMethodTest;

class GiropayTest extends AbstractMethodTest
{
    protected $instance = Giropay::class;

    protected $code = 'giropay';
}
