<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Methods;

use Mollie\Payment\Model\Mollie;

/**
 * Class Creditcard
 *
 * @package Mollie\Payment\Model\Methods
 */
class Creditcard extends Mollie
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = 'mollie_methods_creditcard';

}
