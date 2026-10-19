<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\Order;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Adminhtml\Source\CaptureMoment;

class WhenToCapture
{
    public const CAPTURE_MOMENT = 'mollie_capture_moment';

    public function __construct(
        private readonly Config $config,
    ) {}

    public function onInvoice(OrderInterface $order): bool
    {
        return $this->forOrder($order) === CaptureMoment::ON_INVOICE;
    }

    public function onShipment(OrderInterface $order): bool
    {
        return $this->forOrder($order) === CaptureMoment::ON_SHIPMENT;
    }

    /**
     * The moment is stored on the payment when a manual capture payment is created. The configuration only applies
     * to orders created before this value was stored.
     */
    public function forOrder(OrderInterface $order): ?string
    {
        $payment = $order->getPayment();

        return $payment->getAdditionalInformation()[self::CAPTURE_MOMENT]
            ?? $this->config->whenToCapture($payment->getMethod(), storeId($order->getStoreId()));
    }
}
