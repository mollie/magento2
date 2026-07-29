<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order;

use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;

/**
 * Resolve the order of the checkout session that is still awaiting payment.
 *
 * Used as a fallback when no payment token is available, so an order that was placed while the
 * payment token could not be retrieved is not left behind unpaid.
 */
class OrderAwaitingPaymentFromSession
{
    public function __construct(
        private readonly Session $checkoutSession,
    ) {}

    public function execute(): OrderInterface
    {
        $order = $this->checkoutSession->getLastRealOrder();
        if (!$order->getEntityId()) {
            throw new LocalizedException(__('There is no order available in the current session'));
        }

        if ($order->getState() !== Order::STATE_PENDING_PAYMENT) {
            throw new LocalizedException(
                __('The order %1 of the current session is not awaiting payment', $order->getIncrementId()),
            );
        }

        return $order;
    }
}
