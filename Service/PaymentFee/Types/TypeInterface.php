<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\PaymentFee\Types;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote\Address\Total;
use Mollie\Payment\Service\PaymentFee\Result;

interface TypeInterface
{
    /**
     * @param CartInterface $cart
     * @param Total $total
     * @return Result
     */
    public function calculate(CartInterface $cart, Total $total);
}
