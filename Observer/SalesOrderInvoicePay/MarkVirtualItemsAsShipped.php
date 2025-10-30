<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Observer\SalesOrderInvoicePay;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Mollie\Payment\Model\OrderLines;
use Mollie\Payment\Service\Mollie\MollieApiClient;

class MarkVirtualItemsAsShipped implements ObserverInterface
{
    public function __construct(
        private MollieApiClient $mollieApiClient,
        private OrderLines $orderLines
    ) {}

    public function execute(Observer $observer): void
    {
        /** @var InvoiceInterface $invoice */
        $invoice = $observer->getData('invoice');
        $transactionId = $invoice->getOrder()->getMollieTransactionId();

        $orderLines = $this->getOrderLines($invoice);
        if ($orderLines === null) {
            return;
        }

        $orderLines = array_map(function (OrderLines $orderLine): array {
            return [
                'id' => $orderLine->getLineId(),
                'quantity' => $orderLine->getQtyOrdered(),
            ];
        }, $orderLines);

        $mollieApiClient = $this->mollieApiClient->loadByStore($invoice->getStoreId());
        $mollieOrder = $mollieApiClient->orders->get($transactionId);

        $mollieOrder->createShipment(['lines' => array_values($orderLines)]);
    }

    private function getOrderLines(InvoiceInterface $invoice): ?array
    {
        $orderLines = $this->orderLines->getOrderLinesByOrderId($invoice->getOrderId())->getItems();
        if (!count($orderLines)) {
            return null;
        }

        $orderLines = array_filter($orderLines, function (OrderLines $orderLine): bool {
            return $orderLine->getType() == 'digital';
        });

        if (!count($orderLines)) {
            return null;
        }

        return $orderLines;
    }
}
