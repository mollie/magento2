<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Mollie\Payment\Model\Methods\Trustly;

class TrustlyMethodTest extends AbstractTestMethod
{
    protected $instance = Trustly::class;

    protected $code = 'trustly';
}
