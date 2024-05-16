<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Methods;

use Mollie\Payment\Model\Mollie;

/**
 * Class Bancomatpay
 *
 * @package Mollie\Payment\Model\Methods
 */
class Bancomatpay extends Mollie
{
    /**
     * Payment method code
     *
     * @var string
     */
    const CODE = 'mollie_methods_bancomatpay';
}
