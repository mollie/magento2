<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\GraphQL\Resolver\Cart;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Quote\Api\Data\CartInterface;
use Mollie\Payment\Model\Mollie;
use Mollie\Payment\Service\Mollie\GetIssuers;

class AvailableIssuersForCart implements ResolverInterface
{
    /**
     * @var GetIssuers
     */
    private $getIssuers;

    public function __construct(
        GetIssuers $getIssuers
    ) {
        $this->getIssuers = $getIssuers;
    }

    /**
     * @inheritDoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        /** @var CartInterface $cart */
        $cart = $value['model'];

        $method = $cart->getPayment()->getMethod();
        if (!$method) {
            return null;
        }

        return $this->getIssuers->getForGraphql($cart->getStoreId(), $method);
    }
}
