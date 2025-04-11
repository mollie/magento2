<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Mollie\Payment\Model\Methods\Bancontact;

class BancontactMethodTest extends AbstractTestMethod
{
    protected $instance = Bancontact::class;

    protected $code = 'bancontact';
}
