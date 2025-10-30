<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Fakes\Model\Methods;

use Exception;
use Magento\Sales\Model\Order;
use Mollie\Payment\Model\Methods\Ideal;

class IdealFake extends Ideal
{
    public function startTransaction(Order $order): never
    {
        throw new Exception('[TEST] Transaction failed. Please verify your billing information and payment method, and try again.');
    }
}
