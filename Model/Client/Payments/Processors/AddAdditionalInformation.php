<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model\Client\Payments\Processors;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Api\Resources\Payment;
use Mollie\Payment\Model\Client\PaymentProcessorInterface;
use Mollie\Payment\Model\Client\ProcessTransactionResponse;
use Mollie\Payment\Service\Magento\Vault\AddCardToVault;
use Mollie\Payment\Service\Mollie\DashboardUrl;
use Mollie\Payment\Service\Order\SaveAdditionalInformationDetails;

class AddAdditionalInformation implements PaymentProcessorInterface
{
    public function __construct(
        private DashboardUrl $dashboardUrl,
        private SaveAdditionalInformationDetails $saveAdditionalInformationDetails,
        private AddCardToVault $addCardToVault
    ) {}

    public function process(
        OrderInterface $order,
        Payment $molliePayment,
        string $type,
        ProcessTransactionResponse $response,
    ): ?ProcessTransactionResponse {
        $magentoPayment = $order->getPayment();
        $dashboardUrl = $this->dashboardUrl->forPaymentsApi(storeId($order->getStoreId()), $molliePayment->id);
        $magentoPayment->setAdditionalInformation('dashboard_url', $dashboardUrl);
        $magentoPayment->setAdditionalInformation('mollie_id', $molliePayment->id);
        $magentoPayment->setAdditionalInformation('method', $molliePayment->method);

        $status = $molliePayment->status;
        if ($type == 'webhook' && $magentoPayment->getAdditionalInformation('payment_status') != $status) {
            $magentoPayment->setAdditionalInformation('payment_status', $status);
        }

        if ($molliePayment->details !== null) {
            $this->saveAdditionalInformationDetails->execute($magentoPayment, $molliePayment->details);
        }

        $this->addCardToVault->forPayment($magentoPayment, $molliePayment);

        return $response;
    }
}
