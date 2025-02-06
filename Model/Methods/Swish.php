<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Methods;

use Mollie\Payment\Model\Mollie;

/**
 * Class Swish
 *
 * @package Mollie\Payment\Model\Methods
 */
class Swish extends Mollie
{
    /**
     * Payment method code
     *
     * @var string
     */
    const CODE = 'mollie_methods_swish';
}
