<?php

namespace Mollie\Payment\Test\Fakes\Model\Methods;

use Magento\Sales\Model\Order;
use Mollie\Payment\Model\Methods\Ideal;

class IdealFake extends Ideal
{
    public function startTransaction(Order $order)
    {
        throw new \Exception('[TEST] Transaction failed. Please verify your billing information and payment method, and try again.');
    }

}
