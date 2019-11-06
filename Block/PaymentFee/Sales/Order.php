<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Block\PaymentFee\Sales;

use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Block\Adminhtml\Order\Totals;

class Order extends Template
{
    public function initTotals()
    {
        /** @var Totals $parentBlock */
        $parentBlock = $this->getParentBlock();
        $order = $parentBlock->getOrder();

        if (!($order->getMolliePaymentFee() + $order->getMolliePaymentFeeTax())) {
            return;
        }

        $total = new DataObject([
            'code' => 'mollie_payment_fee',
            'value' => $order->getMolliePaymentFee() + $order->getMolliePaymentFeeTax(),
            'label' => __('Payment Fee'),
        ]);

        $parentBlock->addTotalBefore($total, 'tax');
    }
}
