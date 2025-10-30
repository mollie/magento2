<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model\Client\Payments;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Helper\General as MollieHelper;
use Mollie\Payment\Model\Client\ProcessTransactionResponse;
use Mollie\Payment\Model\Client\ProcessTransactionResponseFactory;
use Mollie\Payment\Service\Mollie\MollieApiClient;
use Mollie\Payment\Service\Mollie\Order\GetTransactionId;

class ProcessTransaction
{
    public function __construct(
        private ProcessTransactionResponseFactory $processTransactionResponseFactory,
        private PaymentProcessors $paymentProcessors,
        private MollieApiClient $mollieApiClient,
        private MollieHelper $mollieHelper,
        private GetTransactionId $getTransactionId
    ) {}

    public function execute(
        OrderInterface $magentoOrder,
        string $type = 'webhook',
    ): ProcessTransactionResponse {
        $mollieApi = $this->mollieApiClient->loadByStore((int) $magentoOrder->getStoreId());
        $transactionId = $this->getTransactionId->forOrder($magentoOrder);
        $molliePayment = $mollieApi->payments->get($transactionId);
        $this->mollieHelper->addTolog($type, $molliePayment);
        $status = $molliePayment->status;

        $defaultResponse = $this->processTransactionResponseFactory->create([
            'success' => true,
            'status' => $status,
            'order_id' => $magentoOrder->getEntityId(),
            'type' => $type,
        ]);

        $this->paymentProcessors->process(
            'preprocess',
            $magentoOrder,
            $molliePayment,
            $type,
            $defaultResponse,
        );

        $refunded = isset($molliePayment->_links->refunds) ? true : false;
        if (in_array($status, ['paid', 'authorized']) && !$refunded) {
            return $this->paymentProcessors->process(
                'paid',
                $magentoOrder,
                $molliePayment,
                $type,
                $defaultResponse,
            );
        }

        if ($refunded) {
            return $this->paymentProcessors->process(
                'refunded',
                $magentoOrder,
                $molliePayment,
                $type,
                $defaultResponse,
            );
        }

        if ($status == 'open') {
            return $this->paymentProcessors->process(
                'open',
                $magentoOrder,
                $molliePayment,
                $type,
                $defaultResponse,
            );
        }

        if ($status == 'pending') {
            return $this->paymentProcessors->process(
                'pending',
                $magentoOrder,
                $molliePayment,
                $type,
                $defaultResponse,
            );
        }

        if ($status == 'expired') {
            return $this->paymentProcessors->process(
                'expired',
                $magentoOrder,
                $molliePayment,
                $type,
                $defaultResponse,
            );
        }

        if ($status == 'canceled' || $status == 'failed' || $status == 'expired') {
            return $this->paymentProcessors->process(
                'failed',
                $magentoOrder,
                $molliePayment,
                $type,
                $defaultResponse,
            );
        }

        throw new LocalizedException(__('Unknown status'));
    }
}
