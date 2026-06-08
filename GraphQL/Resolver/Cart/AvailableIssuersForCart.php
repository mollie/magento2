<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\GraphQL\Resolver\Cart;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Api\Data\CartInterface;
use Mollie\Payment\Service\Mollie\GetIssuers;

class AvailableIssuersForCart implements ResolverInterface
{
    public function __construct(
        private GetIssuers $getIssuers
    ) {}

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

        return $this->getIssuers->getForGraphql(storeId($cart->getStoreId()), $method);
    }
}
