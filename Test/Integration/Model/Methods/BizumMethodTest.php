<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Mollie\Payment\Model\Methods\Bizum;

class BizumMethodTest extends AbstractTestMethod
{
    protected $instance = Bizum::class;

    protected $code = 'bizum';
}
