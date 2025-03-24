<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Mollie\Payment\Model\Methods\Belfius;

class BelfiusMethodTest extends AbstractTestMethod
{
    protected $instance = Belfius::class;

    protected $code = 'belfius';
}
