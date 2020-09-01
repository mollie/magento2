<?php

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Mollie\Payment\Model\Methods\Belfius;

class BelfiusTest extends AbstractMethodTest
{
    protected $instance = Belfius::class;

    protected $code = 'belfius';
}
