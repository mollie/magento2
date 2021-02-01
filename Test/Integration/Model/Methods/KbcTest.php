<?php

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Mollie\Payment\Model\Methods\Kbc;

class KbcTest extends AbstractMethodTest
{
    protected $instance = Kbc::class;

    protected $code = 'kbc';
}
