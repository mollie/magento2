<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 *  * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Webapi;

use Mollie\Payment\Test\Integration\Webapi\AbstractWebApiTest;

class StartTransactionTest extends AbstractWebApiTest
{
    /**
     * @var string
     */
    protected $class = StartTransaction::class;

    protected $methods = ['execute'];
}