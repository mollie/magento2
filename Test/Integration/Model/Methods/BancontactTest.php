<?php

namespace Mollie\Payment\Model\Methods;

use Mollie\Payment\Test\Integration\Model\Methods\AbstractMethodTest;

class BancontactTest extends AbstractMethodTest
{
    protected $instance = Bancontact::class;

    protected $code = 'bancontact';
}
