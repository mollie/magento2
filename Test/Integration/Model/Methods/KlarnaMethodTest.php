<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Mollie\Payment\Model\Methods\Klarna;

class KlarnaMethodTest extends AbstractTestMethod
{
    protected $instance = Klarna::class;

    protected $code = 'klarna';
}
