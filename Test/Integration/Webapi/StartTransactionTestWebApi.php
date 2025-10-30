<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Webapi;

use Mollie\Payment\Webapi\StartTransaction;

class StartTransactionTestWebApi extends AbstractTestWebApi
{
    /**
     * @var string
     */
    protected $class = StartTransaction::class;

    protected $methods = ['execute'];
}
