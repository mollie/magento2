<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Mollie\Payment\Model\Methods\Directdebit;

class DirectdebitMethodTest extends AbstractTestMethod
{
    protected ?string $instance = Directdebit::class;

    protected ?string $code = 'directdebit';
}
