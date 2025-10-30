<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Mollie\Payment\Model\Methods\Multibanco;

class MultibancoMethodTest extends AbstractTestMethod
{
    protected ?string $instance = Multibanco::class;

    protected ?string $code = 'multibanco';
}
