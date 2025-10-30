<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Block\PaymentFee\Sales;

use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Block\Adminhtml\Order\Creditmemo\Totals;
use Mollie\Payment\Service\Order\Creditmemo as CreditmemoService;

class CreditmemoNew extends Template
{
    public function __construct(
        Context $context,
        private CreditmemoService $creditmemoService,
        array $data = [],
    ) {
        parent::__construct($context, $data);
    }

    public function initTotals(): void
    {
        /** @var Totals $parentBlock */
        $parentBlock = $this->getParentBlock();
        $order = $parentBlock->getOrder();
        $creditmemo = $parentBlock->getCreditmemo();

        if (!($order->getMolliePaymentFee() + $order->getMolliePaymentFeeTax())) {
            return;
        }

        if (!$this->creditmemoService->isFullOrLastPartialCreditmemo($creditmemo)) {
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
