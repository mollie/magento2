<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Block\PaymentFee\Sales;

use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Block\Adminhtml\Order\Creditmemo\Totals;
use Mollie\Payment\Service\Order\Creditmemo as CreditmemoService;

class CreditmemoNew extends Template
{
    /**
     * @var CreditmemoService
     */
    private $creditmemoService;

    public function __construct(
        Template\Context $context,
        CreditmemoService $creditmemoService,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->creditmemoService = $creditmemoService;
    }

    public function initTotals()
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
