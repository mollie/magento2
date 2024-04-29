<?php

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Mollie\Payment\Model\Methods\Bancomat;

class BancomatTest extends AbstractMethodTest
{
    protected $instance = Bancomat::class;

    protected $code = 'bancomat';
}
