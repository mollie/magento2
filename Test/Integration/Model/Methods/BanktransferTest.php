<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 *  * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Methods;

use Mollie\Payment\Test\Integration\Model\Methods\AbstractMethodTest;

class BanktransferTest extends AbstractMethodTest
{
    protected $instance = Banktransfer::class;

    protected $code = 'banktransfer';
}
