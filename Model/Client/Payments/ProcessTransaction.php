<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Client\Payments;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Mollie\Payment\Helper\General as MollieHelper;
use Mollie\Payment\Model\Client\ProcessTransactionResponse;
use Mollie\Payment\Model\Client\ProcessTransactionResponseFactory;
use Mollie\Payment\Model\OrderLines;
use Mollie\Payment\Service\Mollie\MollieApiClient;

class ProcessTransaction
{
    /**
     * @var ProcessTransactionResponseFactory
     */
    private $processTransactionResponseFactory;

    /**
     * @var PaymentProcessors
     */
    private $paymentProcessors;

    /**
     * @var MollieApiClient
     */
    private $mollieApiClient;

    /**
     * @var MollieHelper
     */
    private $mollieHelper;

    public function __construct(
        ProcessTransactionResponseFactory $processTransactionResponseFactory,
        PaymentProcessors $paymentProcessors,
        MollieApiClient $mollieApiClient,
        MollieHelper $mollieHelper
    ) {
        $this->processTransactionResponseFactory = $processTransactionResponseFactory;
        $this->paymentProcessors = $paymentProcessors;
        $this->mollieApiClient = $mollieApiClient;
        $this->mollieHelper = $mollieHelper;
    }

    public function execute(
        OrderInterface $magentoOrder,
        string $type = 'webhook'
    ): ProcessTransactionResponse {
        $mollieApi = $this->mollieApiClient->loadByStore((int)$magentoOrder->getStoreId());
        $molliePayment = $mollieApi->payments->get($magentoOrder->getMollieTransactionId());
        $this->mollieHelper->addTolog($type, $molliePayment);
        $status = $molliePayment->status;

        $defaultResponse = $this->processTransactionResponseFactory->create([
            'success' => true,
            'status' => $status,
            'order_id' => $magentoOrder->getEntityId(),
            'type' => $type
        ]);

        $this->paymentProcessors->process(
            'preprocess',
            $magentoOrder,
            $molliePayment,
            $type,
            $defaultResponse
        );

        $refunded = isset($molliePayment->_links->refunds) ? true : false;
        if (in_array($status, ['paid', 'authorized']) && !$refunded) {
            return $this->paymentProcessors->process(
                'paid',
                $magentoOrder,
                $molliePayment,
                $type,
                $defaultResponse
            );
        }

        if ($refunded) {
            return $this->paymentProcessors->process(
                'refunded',
                $magentoOrder,
                $molliePayment,
                $type,
                $defaultResponse
            );
        }

        if ($status == 'open') {
            return $this->paymentProcessors->process(
                'open',
                $magentoOrder,
                $molliePayment,
                $type,
                $defaultResponse
            );
        }

        if ($status == 'pending') {
            return $this->paymentProcessors->process(
                'pending',
                $magentoOrder,
                $molliePayment,
                $type,
                $defaultResponse
            );
        }

        if ($status == 'expired') {
            return $this->paymentProcessors->process(
                'expired',
                $magentoOrder,
                $molliePayment,
                $type,
                $defaultResponse
            );
        }

        if ($status == 'canceled' || $status == 'failed' || $status == 'expired') {
            return $this->paymentProcessors->process(
                'failed',
                $magentoOrder,
                $molliePayment,
                $type,
                $defaultResponse
            );
        }

        throw new \Exception('Unknown status');
    }
}
