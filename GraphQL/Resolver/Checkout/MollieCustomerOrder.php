<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\GraphQL\Resolver\Checkout;

use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\SalesGraphQl\Model\Formatter\Order as OrderFormatter;

class MollieCustomerOrder implements ResolverInterface
{
    public function __construct(
        private Encryptor $encryptor,
        private OrderRepositoryInterface $orderRepository,
        private ObjectManagerInterface $objectManager
    ) {}

    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        $hash = $args['hash'];
        // @phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
        $decodedHash = base64_decode($hash);

        $orderId = $this->encryptor->decrypt($decodedHash);
        $order = $this->orderRepository->get($orderId);

        /**
         * This class exists from Magento 2.4.2, but we need to support lower versions too so use the Object Manager
         * to load the class if it exists.
         */
        if (class_exists(OrderFormatter::class)) {
            $orderFormatter = $this->objectManager->get(OrderFormatter::class);

            $result = $orderFormatter->format($order);
            $result['model'] = $order;

            return $result;
        }

        return [
            'id' => $order->getEntityId(),
            'increment_id' => $order->getIncrementId(),
            'created_at' => $order->getCreatedAt(),
            'grand_total' => $order->getGrandTotal(),
            'status' => $order->getStatus(),
        ];
    }
}
