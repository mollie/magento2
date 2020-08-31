<?php

namespace Mollie\Payment\Model\Methods;

use Mollie\Payment\Test\Integration\Model\Methods\AbstractMethodTest;

class KbcTest extends AbstractMethodTest
{
    protected $instance = Kbc::class;

    protected $code = 'kbc';
}
