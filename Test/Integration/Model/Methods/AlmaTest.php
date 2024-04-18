<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Mollie\Payment\Model\Methods\Alma;

class AlmaTest extends AbstractMethodTest
{
    protected $instance = Alma::class;

    protected $code = 'alma';
}
