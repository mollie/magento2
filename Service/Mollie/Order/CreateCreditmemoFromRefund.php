<?php

namespace Mollie\Payment\Service\Mollie\Order;

use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Controller\Adminhtml\Order\CreditmemoLoader;
use Magento\Sales\Model\Service\CreditmemoService;
use Mollie\Api\Resources\Refund;

class CreateCreditmemoFromRefund
{
    public function __construct(
        private readonly CreditmemoLoader $creditmemoLoader,
        private readonly CreditmemoService $creditmemoService
    ) {}

    public function execute(OrderInterface $order, InvoiceInterface $invoice, Refund $refund): void
    {
        $order->getPayment()->setTransactionId($refund->id);
        $order->getPayment()->setLastTransId($refund->id);

        $qtys = [];
        foreach ($order->getAllItems() as $item) {
            $qtys[$item->getId()]['qty'] = 0;
        }

        $this->creditmemoLoader->setInvoiceId($invoice->getId());
        $this->creditmemoLoader->setOrderId($order->getId());
        $this->creditmemoLoader->setCreditmemo([
            'shipping_amount' => 0,
            'adjustment_negative' => 0,
            'adjustment_positive' => $refund->amount->value,
            'items' => $qtys,
        ]);

        $creditmemo = $this->creditmemoLoader->load();

        if (!$creditmemo) {
            return;
        }

        // Prevent a refund loop by setting the Mollie transaction ID so we can recognize that the refund
        // has already been processed on the Mollie side.
        $creditmemo->setMollieTransactionId($refund->id);

        $creditmemo->addComment(
            __('This refund was created outside Magento, possible through the Mollie Dashboard.'),
            false,
            false,
        );

        $this->creditmemoService->refund($creditmemo, false);
    }
}
