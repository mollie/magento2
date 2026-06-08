<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Mollie\Payment\Model\Methods\In3;

class In3MethodTest extends AbstractTestMethod
{
    protected ?string $instance = In3::class;

    protected ?string $code = 'in3';
}
