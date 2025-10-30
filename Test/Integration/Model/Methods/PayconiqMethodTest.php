<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Mollie\Payment\Model\Methods\Payconiq;

class PayconiqMethodTest extends AbstractTestMethod
{
    protected ?string $instance = Payconiq::class;

    protected ?string $code = 'payconiq';
}
