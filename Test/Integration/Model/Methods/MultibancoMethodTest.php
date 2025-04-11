<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Mollie\Payment\Model\Methods\Multibanco;

class MultibancoMethodTest extends AbstractTestMethod
{
    protected $instance = Multibanco::class;

    protected $code = 'multibanco';
}
