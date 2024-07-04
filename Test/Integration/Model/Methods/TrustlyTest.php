<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Mollie\Payment\Model\Methods\Trustly;

class TrustlyTest extends AbstractMethodTest
{
    protected $instance = Trustly::class;

    protected $code = 'trustly';
}
