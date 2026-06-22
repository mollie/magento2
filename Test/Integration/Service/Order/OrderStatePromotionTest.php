<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Order;

use Magento\Sales\Model\Order;
use Mollie\Payment\Service\Order\OrderStatePromotion;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class OrderStatePromotionTest extends IntegrationTestCase
{
    public function testPendingPaymentCanBePromotedToProcessing(): void
    {
        $promotion = new OrderStatePromotion();

        $this->assertTrue($promotion->canBePromotedToProcessing(Order::STATE_PENDING_PAYMENT));
    }

    public function testPaymentReviewCanBePromotedToProcessing(): void
    {
        $promotion = new OrderStatePromotion();

        $this->assertTrue($promotion->canBePromotedToProcessing(Order::STATE_PAYMENT_REVIEW));
    }

    public function testProcessingCannotBePromotedAgain(): void
    {
        $promotion = new OrderStatePromotion();

        $this->assertFalse($promotion->canBePromotedToProcessing(Order::STATE_PROCESSING));
    }

    public function testCompleteCannotBePromoted(): void
    {
        $promotion = new OrderStatePromotion();

        $this->assertFalse($promotion->canBePromotedToProcessing(Order::STATE_COMPLETE));
    }

    public function testNullStateCannotBePromoted(): void
    {
        $promotion = new OrderStatePromotion();

        $this->assertFalse($promotion->canBePromotedToProcessing(null));
    }
}
