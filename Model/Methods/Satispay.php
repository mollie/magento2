<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Methods;

use Mollie\Payment\Model\Mollie;

/**
 * Class Satispay
 *
 * @package Mollie\Payment\Model\Methods
 */
class Satispay extends Mollie
{
    /**
     * Payment method code
     *
     * @var string
     */
    const CODE = 'mollie_methods_satispay';
}
