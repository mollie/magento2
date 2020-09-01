<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 *  * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Mollie\Payment\Model\Methods\Eps;

class EpsTest extends AbstractMethodTest
{
    protected $instance = Eps::class;

    protected $code = 'eps';
}
