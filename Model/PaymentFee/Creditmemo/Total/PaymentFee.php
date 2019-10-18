<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\PaymentFee\Creditmemo\Total;

use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal;
use Mollie\Payment\Service\Order\Creditmemo as CreditmemoService;

class PaymentFee extends AbstractTotal
{
    /**
     * @var CreditmemoService
     */
    private $creditmemoService;

    public function __construct(
        CreditmemoService $creditmemoService,
        array $data = []
    ) {
        $this->creditmemoService = $creditmemoService;
        parent::__construct($data);
    }

    /**
     * @param Creditmemo $creditmemo
     * @return $this|AbstractTotal
     */
    public function collect(Creditmemo $creditmemo)
    {
        if (!$this->creditmemoService->isFullOrLastPartialCreditmemo($creditmemo)) {
            return $this;
        }

        $order = $creditmemo->getOrder();
        $paymentFee = $order->getData('mollie_payment_fee');
        $basePaymentFee = $order->getData('base_mollie_payment_fee');
        $paymentFeeTax = $order->getData('mollie_payment_fee_tax');
        $basePaymentFeeTax = $order->getData('base_mollie_payment_fee_tax');

        $creditmemo->setData('mollie_payment_fee', $paymentFee);
        $creditmemo->setData('base_mollie_payment_fee', $basePaymentFee);
        $creditmemo->setData('mollie_payment_fee_tax', $paymentFeeTax);
        $creditmemo->setData('base_mollie_payment_fee_tax', $basePaymentFeeTax);

        $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $paymentFee + $paymentFeeTax);
        $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $basePaymentFee + $basePaymentFeeTax);
        $creditmemo->setTaxAmount($creditmemo->getTaxAmount() + $paymentFeeTax);
        $creditmemo->setBaseTaxAmount($creditmemo->getBaseTaxAmount() + $basePaymentFeeTax);

        return $this;
    }
}
