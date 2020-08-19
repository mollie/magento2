<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 *  * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Webapi;

use Mollie\Payment\Test\Integration\Webapi\AbstractWebApiTest;

class GetCustomerOrderTest extends AbstractWebApiTest
{
    /**
     * @var string
     */
    protected $class = GetCustomerOrder::class;

    /**
     * @var string[]
     */
    protected $methods = ['byHash'];
}