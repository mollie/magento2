<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Webapi;

use Exception;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Exception\NotFoundException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mollie\Payment\Api\PaymentTokenRepositoryInterface;
use Mollie\Payment\Api\Webapi\GetCustomerOrderInterface;
use Mollie\Payment\Service\Mollie\GetMollieStatus;
use Mollie\Payment\Service\Mollie\GetMollieStatusResult;

class GetCustomerOrder implements GetCustomerOrderInterface
{
    public function __construct(
        readonly private Encryptor $encryptor,
        readonly private OrderRepositoryInterface $orderRepository,
        readonly private GetMollieStatus $getMollieStatus,
        readonly private PaymentTokenRepositoryInterface $paymentTokenRepository,
    ) {}

    /**
     *
     * @param string $hash
     * @return mixed[]
     * @throws Exception
     */
    public function byHash(string $hash): array
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
        $decodedHash = base64_decode($hash);

        $orderId = $this->encryptor->decrypt($decodedHash);
        $order = $this->orderRepository->get($orderId);

        return $this->outputOrder($order);
    }

    public function byPaymentToken(string $token): array
    {
        $result = $this->paymentTokenRepository->getByToken($token);
        if ($result->getOrderId() == null) {
            throw new NotFoundException(__('Token found, but there is order connected'));
        }

        $order = $this->orderRepository->get($result->getOrderId());

        return $this->outputOrder($order);
    }

    private function mapMollieStatusToMagentoStatus(GetMollieStatusResult $mollieResult): string
    {
        if (in_array($mollieResult->getStatus(), ['paid', 'authorized'])) {
            return 'processing';
        }

        if (in_array($mollieResult->getStatus(), ['canceled', 'expired', 'failed'])) {
            return 'canceled';
        }

        if (in_array($mollieResult->getStatus(), ['completed'])) {
            return 'complete';
        }

        return 'pending';
    }

    private function outputOrder(OrderInterface $order): array
    {
        $mollieResult = $this->getMollieStatus->execute((int)$order->getEntityId());

        return [
            [
                'id' => $order->getEntityId(),
                'increment_id' => $order->getIncrementId(),
                'created_at' => $order->getCreatedAt(),
                'grand_total' => $order->getGrandTotal(),
                'status' => $this->mapMollieStatusToMagentoStatus($mollieResult),
            ],
        ];
    }
}
