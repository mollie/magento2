<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Mollie\Payment\Model\Methods\Kbc;

class KbcMethodTest extends AbstractTestMethod
{
    protected $instance = Kbc::class;

    protected $code = 'kbc';
}
