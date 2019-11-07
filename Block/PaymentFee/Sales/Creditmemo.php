<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Block\PaymentFee\Sales;

use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Block\Adminhtml\Order\Creditmemo\Totals;

class Creditmemo extends Template
{
    public function initTotals()
    {
        /** @var Totals $parentBlock */
        $parentBlock = $this->getParentBlock();
        $creditmemo = $parentBlock->getCreditmemo();

        if (!(int)$creditmemo->getBaseMolliePaymentFee()) {
            return;
        }

        $total = new DataObject([
            'code' => 'mollie_payment_fee',
            'value' => $creditmemo->getMolliePaymentFee() + $creditmemo->getMolliePaymentFeeTax(),
            'label' => __('Payment Fee'),
        ]);

        $parentBlock->addTotalBefore($total, 'tax');
    }
}
