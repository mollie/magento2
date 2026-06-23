<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order;

use Magento\Sales\Model\Order;

class OrderStatePromotion
{
    private const PROMOTABLE_STATES = [
        Order::STATE_PENDING_PAYMENT,
        Order::STATE_PAYMENT_REVIEW,
    ];

    public function canBePromotedToProcessing(?string $state): bool
    {
        return in_array($state, self::PROMOTABLE_STATES, true);
    }
}
