<?php

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Mollie\Payment\Model\Methods\Bancontact;

class BancontactTest extends AbstractMethodTest
{
    protected $instance = Bancontact::class;

    protected $code = 'bancontact';
}
