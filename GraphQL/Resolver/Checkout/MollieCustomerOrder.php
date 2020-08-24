<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 *  * See COPYING.txt for license details.
 */

namespace Mollie\Payment\GraphQL\Resolver\Checkout;

use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Sales\Api\OrderRepositoryInterface;

class MollieCustomerOrder implements ResolverInterface
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

    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $hash = $args['hash'];
        $decodedHash = base64_decode($hash);

        $orderId = $this->encryptor->decrypt($decodedHash);
        $order = $this->orderRepository->get($orderId);

        return [
            'id' => $order->getEntityId(),
            'increment_id' => $order->getIncrementId(),
            'created_at' => $order->getCreatedAt(),
            'grand_total' => $order->getGrandTotal(),
            'status' => $order->getStatus(),
        ];
    }
}