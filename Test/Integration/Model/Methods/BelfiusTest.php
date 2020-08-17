<?php

namespace Mollie\Payment\Model\Methods;

use Mollie\Payment\Test\Integration\Model\Methods\AbstractMethodTest;

class BelfiusTest extends AbstractMethodTest
{
    protected $instance = Belfius::class;

    protected $code = 'belfius';
}
