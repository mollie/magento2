<?php

namespace Mollie\Payment\Observer\SalesOrderInvoicePay;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Mollie\Payment\Model\OrderLines;
use Mollie\Payment\Service\Mollie\MollieApiClient;

class MarkVirtualItemsAsShipped implements ObserverInterface
{
    /**
     * @var MollieApiClient
     */
    private $mollieApiClient;

    /**
     * @var OrderLines
     */
    private $orderLines;

    public function __construct(
        MollieApiClient $mollieApiClient,
        OrderLines $orderLines
    ) {
        $this->mollieApiClient = $mollieApiClient;
        $this->orderLines = $orderLines;
    }

    public function execute(Observer $observer)
    {
        /** @var InvoiceInterface $invoice */
        $invoice = $observer->getData('invoice');
        $transactionId = $invoice->getOrder()->getMollieTransactionId();

        // Check if the Orders API is used.
        if (!$transactionId || substr($transactionId, 0, 3) !== 'ord') {
            return;
        }

        $orderLines = $this->getOrderLines($invoice);
        if ($orderLines === null) {
            return;
        }

        $orderLines = array_map(function (OrderLines $orderLine) {
            return [
                'id' => $orderLine->getLineId(),
                'quantity' => $orderLine->getQtyOrdered()
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

        $orderLines = array_filter($orderLines, function (OrderLines $orderLine) {
            return $orderLine->getType() == 'digital';
        });

        if (!count($orderLines)) {
            return null;
        }

        return $orderLines;
    }
}
