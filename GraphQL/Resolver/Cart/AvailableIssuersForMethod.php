<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\GraphQL\Resolver\Cart;

use Mollie\Payment\Service\Mollie\GetIssuers;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class AvailableIssuersForMethod implements ResolverInterface
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
        $storeId = $context->getExtensionAttributes()->getStore()->getId();
        $method = $value['code'];

        if (!$method || strpos($method, 'mollie_methods') === false) {
            return null;
        }

        return $this->getIssuers->getForGraphql($storeId, $method);
    }
}
