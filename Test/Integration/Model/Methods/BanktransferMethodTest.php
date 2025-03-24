<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Mollie\Payment\Model\Methods\Banktransfer;

class BanktransferMethodTest extends AbstractTestMethod
{
    protected $instance = Banktransfer::class;

    protected $code = 'banktransfer';
}
