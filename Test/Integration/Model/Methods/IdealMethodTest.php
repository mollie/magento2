<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Mollie\Payment\Model\Methods\Ideal;

class IdealMethodTest extends AbstractTestMethod
{
    protected $instance = Ideal::class;

    protected $code = 'ideal';
}
