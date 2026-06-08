<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Mollie\Payment\Model\Methods\Przelewy24;

class Przelewy24MethodTest extends AbstractTestMethod
{
    protected ?string $instance = Przelewy24::class;

    protected ?string $code = 'przelewy24';
}
