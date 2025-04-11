<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Mollie\Payment\Model\Methods\Alma;

class AlmaMethodTest extends AbstractTestMethod
{
    protected $instance = Alma::class;

    protected $code = 'alma';
}
