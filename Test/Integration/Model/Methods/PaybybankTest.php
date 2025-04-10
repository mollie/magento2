<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Mollie\Payment\Model\Methods\Paybybank;

class PaybybankTest extends AbstractTestMethod
{
    protected $instance = Paybybank::class;

    protected $code = 'paybybank';
}
