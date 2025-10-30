<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Webapi;

use Exception;
use Magento\Framework\Encryption\Encryptor;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mollie\Payment\Api\Webapi\GetCustomerOrderInterface;
use Mollie\Payment\Service\Mollie\GetMollieStatus;
use Mollie\Payment\Service\Mollie\GetMollieStatusResult;

class GetCustomerOrder implements GetCustomerOrderInterface
{
    public function __construct(
        private Encryptor $encryptor,
        private OrderRepositoryInterface $orderRepository,
        private GetMollieStatus $getMollieStatus
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

        $mollieResult = $this->getMollieStatus->execute($orderId);

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

    public function mapMollieStatusToMagentoStatus(GetMollieStatusResult $mollieResult): string
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
}
