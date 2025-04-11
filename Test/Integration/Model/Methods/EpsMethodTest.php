<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Mollie\Payment\Model\Methods\Eps;

class EpsMethodTest extends AbstractTestMethod
{
    protected $instance = Eps::class;

    protected $code = 'eps';
}
