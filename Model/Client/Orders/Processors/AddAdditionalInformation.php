<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Client\Orders\Processors;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Api\Resources\Order;
use Mollie\Payment\Model\Client\OrderProcessorInterface;
use Mollie\Payment\Model\Client\ProcessTransactionResponse;
use Mollie\Payment\Service\Magento\Vault\AddCardToVault;
use Mollie\Payment\Service\Mollie\DashboardUrl;

class AddAdditionalInformation implements OrderProcessorInterface
{
    /**
     * @var DashboardUrl
     */
    private $dashboardUrl;

    /**
     * @var AddCardToVault
     */
    private $addCardToVault;

    public function __construct(
        DashboardUrl $dashboardUrl,
        AddCardToVault $addCardToVault
    ) {
        $this->dashboardUrl = $dashboardUrl;
        $this->addCardToVault = $addCardToVault;
    }

    public function process(OrderInterface $magentoOrder, Order $mollieOrder, string $type, ProcessTransactionResponse $response): ?ProcessTransactionResponse
    {
        if ($mollieOrder->payments() &&
            $mollieOrder->payments()->offsetGet(0) &&
            isset($mollieOrder->payments()->offsetGet(0)->metadata->refunded)
        ) {
            return $response;
        }

        if ($mollieOrder->isCompleted()) {
            return $response;
        }

        $dashboardUrl = $this->dashboardUrl->forOrdersApi($magentoOrder->getStoreId(), $mollieOrder->id);
        $magentoOrder->getPayment()->setAdditionalInformation('mollie_id', $mollieOrder->id);
        $magentoOrder->getPayment()->setAdditionalInformation('dashboard_url', $dashboardUrl);
        $magentoOrder->getPayment()->setAdditionalInformation('method', $mollieOrder->method);

        $status = $mollieOrder->status;
        $payment = $magentoOrder->getPayment();
        $this->addCardToVault->forPayment($payment, $mollieOrder);
        if ($type == 'webhook' && $payment->getAdditionalInformation('payment_status') != $status) {
            $payment->setAdditionalInformation('payment_status', $status);
        }

        return $response;
    }
}
