<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Client\Payments\Processors;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Api\Resources\Payment;
use Mollie\Payment\Model\Client\PaymentProcessorInterface;
use Mollie\Payment\Model\Client\ProcessTransactionResponse;
use Mollie\Payment\Service\Mollie\DashboardUrl;

class AddAdditionalInformation implements PaymentProcessorInterface
{
    /**
     * @var DashboardUrl
     */
    private $dashboardUrl;

    public function __construct(
        DashboardUrl $dashboardUrl
    ) {
        $this->dashboardUrl = $dashboardUrl;
    }

    public function process(
        OrderInterface $order,
        Payment $molliePayment,
        string $type,
        ProcessTransactionResponse $response
    ): ?ProcessTransactionResponse {
        $magentoPayment = $order->getPayment();
        $dashboardUrl = $this->dashboardUrl->forPaymentsApi($order->getStoreId(), $molliePayment->id);
        $magentoPayment->setAdditionalInformation('dashboard_url', $dashboardUrl);
        $magentoPayment->setAdditionalInformation('mollie_id', $molliePayment->id);

        $status = $molliePayment->status;
        if ($type == 'webhook' && $magentoPayment->getAdditionalInformation('payment_status') != $status) {
            $magentoPayment->setAdditionalInformation('payment_status', $status);
        }

        if ($molliePayment->details !== null) {
            $magentoPayment->setAdditionalInformation('details', json_encode($molliePayment->details));
        }

        return $response;
    }
}
