<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Mollie\Payment\Model\Methods\Sofort;

class SofortMethodTest extends AbstractTestMethod
{
    protected $instance = Sofort::class;

    protected $code = 'sofort';
}
