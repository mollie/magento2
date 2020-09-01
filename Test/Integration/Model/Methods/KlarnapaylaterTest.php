<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 *  * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Mollie\Payment\Model\Methods\Klarnapaylater;

class KlarnapaylaterTest extends AbstractMethodTest
{
    protected $instance = Klarnapaylater::class;

    protected $code = 'klarnapaylater';
}
