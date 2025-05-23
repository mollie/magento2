<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Mollie\Payment\Model\Methods\Swish;

class SwishMethodTest extends AbstractTestMethod
{
    protected $instance = Swish::class;

    protected $code = 'swish';
}
