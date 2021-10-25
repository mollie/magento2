<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 *  * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Mollie\Payment\Model\Methods\Klarnapaynow;

class KlarnapaynowTest extends AbstractMethodTest
{
    protected $instance = Klarnapaynow::class;

    protected $code = 'klarnapaynow';
}
