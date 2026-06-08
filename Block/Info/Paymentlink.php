<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Block\Info;

class Paymentlink extends Base
{
    /**
     * @var string
     */
    protected $_template = 'Mollie_Payment::info/mollie_paymentlink.phtml';
}
