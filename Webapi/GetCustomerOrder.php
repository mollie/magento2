<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 *  * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Webapi;

use Magento\Framework\Encryption\Encryptor;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mollie\Payment\Api\Webapi\GetCustomerOrderInterface;

class GetCustomerOrder implements GetCustomerOrderInterface
{
    /**
     * @var Encryptor
     */
    private $encryptor;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    public function __construct(
        Encryptor $encryptor,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->encryptor = $encryptor;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param string $hash
     * @return mixed[]
     * @throws \Exception
     */
    public function byHash(string $hash): array
    {
        $decodedHash = base64_decode($hash);

        $orderId = $this->encryptor->decrypt($decodedHash);
        $order = $this->orderRepository->get($orderId);

        return [
            [
                'id' => $order->getEntityId(),
                'increment_id' => $order->getIncrementId(),
                'created_at' => $order->getCreatedAt(),
                'grand_total' => $order->getGrandTotal(),
                'status' => $order->getStatus(),
            ]
        ];
    }
}