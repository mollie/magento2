<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Mollie\Payment\Model\Methods\Bizum;

class BizumMethodTest extends AbstractTestMethod
{
    protected ?string $instance = Bizum::class;

    protected ?string $code = 'bizum';
}
