<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Mollie\Payment\Model\Methods\Satispay;

class SatispayTest extends AbstractMethodTest
{
    protected $instance = Satispay::class;

    protected $code = 'satispay';
}
