<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\Order;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Exceptions\PartialCaptureNotSupported;

class ValidatePartialCapture
{
    public function __construct(
        private readonly Config $config,
        private readonly PriceCurrencyInterface $priceCurrency,
        private readonly SupportsPartialCapture $supportsPartialCapture,
    ) {}

    /**
     * @throws PartialCaptureNotSupported
     */
    public function execute(InvoiceInterface $invoice): void
    {
        $order = $invoice->getOrder();

        if ($this->supportsPartialCapture->execute($order)) {
            return;
        }

        if ($this->coversTheCompleteOrder($invoice, $order)) {
            return;
        }

        throw new PartialCaptureNotSupported(
            __(
                '%1 does not support partial captures. Create a single invoice or shipment for the complete order, or cancel the order to release the authorization.',
                $this->getMethodTitle($order),
            ),
        );
    }

    private function coversTheCompleteOrder(InvoiceInterface $invoice, OrderInterface $order): bool
    {
        return $this->priceCurrency->round($invoice->getBaseGrandTotal())
            === $this->priceCurrency->round($order->getBaseGrandTotal());
    }

    private function getMethodTitle(OrderInterface $order): string
    {
        $method = $order->getPayment()->getMethod();

        return $this->config->getMethodTitle($method, storeId($order->getStoreId())) ?: $method;
    }
}
