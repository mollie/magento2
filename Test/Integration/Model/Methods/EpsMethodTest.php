<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Mollie\Payment\Model\Methods\Eps;

class EpsMethodTest extends AbstractTestMethod
{
    protected ?string $instance = Eps::class;

    protected ?string $code = 'eps';
}
