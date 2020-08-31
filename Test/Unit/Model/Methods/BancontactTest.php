<?php

namespace Mollie\Payment\Test\Unit\Model\Methods;

use Mollie\Payment\Model\Methods\Bancontact;
use Mollie\Payment\Test\Unit\Model\Methods\AbstractMethodTest;

class BancontactTest extends AbstractMethodTest
{
    protected $instance = Bancontact::class;

    protected $code = 'bancontact';
}
