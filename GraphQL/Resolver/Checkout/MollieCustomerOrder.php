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
use Magento\SalesGraphQl\Model\Formatter\Order as OrderFormatter;

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

    /**
     * @var OrderFormatter
     */
    private $orderFormatter;

    public function __construct(
        Encryptor $encryptor,
        OrderRepositoryInterface $orderRepository,
        OrderFormatter $orderFormatter
    ) {
        $this->encryptor = $encryptor;
        $this->orderRepository = $orderRepository;
        $this->orderFormatter = $orderFormatter;
    }

    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $hash = $args['hash'];
        $decodedHash = base64_decode($hash);

        $orderId = $this->encryptor->decrypt($decodedHash);
        $order = $this->orderRepository->get($orderId);

        $result = $this->orderFormatter->format($order);
        $result['model'] = $order;

        return $result;
    }
}
