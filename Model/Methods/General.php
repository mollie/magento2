<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Methods;

use Mollie\Payment\Model\Mollie;

/**
 * Class General
 *
 * @package Mollie\Payment\Model\Methods
 */
class General extends Mollie
{
    /**
     * Payment method code
     *
     * @var string
     */
    const CODE = 'mollie_methods_general';
}
