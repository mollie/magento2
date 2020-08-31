<?php

namespace Mollie\Payment\Test\Unit\Model\Methods;

use Mollie\Payment\Model\Methods\Belfius;
use Mollie\Payment\Test\Unit\Model\Methods\AbstractMethodTest;

class BelfiusTest extends AbstractMethodTest
{
    protected $instance = Belfius::class;

    protected $code = 'belfius';
}
