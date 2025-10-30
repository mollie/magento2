<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Webapi;

use Mollie\Payment\Test\Integration\Webapi\AbstractTestWebApi;

class GetCustomerOrderTestWebApi extends AbstractTestWebApi
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
