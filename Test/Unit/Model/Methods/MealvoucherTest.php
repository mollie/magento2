<?php

namespace Mollie\Payment\Test\Unit\Model\Methods;

use Mollie\Payment\Model\Methods\Mealvoucher;
use Mollie\Payment\Test\Unit\Model\Methods\AbstractMethodTest;

class MealvoucherTest extends AbstractMethodTest
{
    protected $instance = Mealvoucher::class;

    protected $code = 'mealvoucher';
}
