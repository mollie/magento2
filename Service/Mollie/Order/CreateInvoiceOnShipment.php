<?php

namespace Mollie\Payment\Service\Mollie\Order;

use Magento\Sales\Api\Data\OrderInterface;

class CreateInvoiceOnShipment
{
    /**
     * @var CanUseManualCapture
     */
    private $canUseManualCapture;
    /**
     * @var UsedMollieApi
     */
    private $usedMollieApi;

    public function __construct(
        CanUseManualCapture $canUseManualCapture,
        UsedMollieApi $usedMollieApi
    ) {
        $this->canUseManualCapture = $canUseManualCapture;
        $this->usedMollieApi = $usedMollieApi;
    }

    public function execute(OrderInterface $order): bool
    {
        $methodCode = $order->getPayment()->getMethod();
        if (in_array($methodCode, [
            'mollie_methods_billie',
            'mollie_methods_klarna',
            'mollie_methods_klarnapaylater',
            'mollie_methods_klarnapaynow',
            'mollie_methods_klarnasliceit',
            'mollie_methods_in3',
        ])) {
            return true;
        }

        if ($this->usedMollieApi->execute($order) == UsedMollieApi::TYPE_PAYMENTS &&
            $this->canUseManualCapture->execute($order)
        ) {
            return false;
        }

        return true;
    }
}
