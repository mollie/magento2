<?php
/**
 * Copyright © 2017 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Methods;

use Mollie\Payment\Model\Mollie;

class Giftcard extends Mollie
{

    protected $_code = 'mollie_methods_giftcard';
    protected $_canRefund = false;
    protected $_canRefundInvoicePartial = false;
}
