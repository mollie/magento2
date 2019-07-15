<?php

namespace Mollie\Payment\Test\Fakes\Model\Methods;

use Magento\Sales\Model\Order;
use Mollie\Payment\Model\Methods\Ideal;

class IdealFake extends Ideal
{
    public function startTransaction(Order $order)
    {
        throw new \Exception('[TEST] Something went wrong');
    }

}