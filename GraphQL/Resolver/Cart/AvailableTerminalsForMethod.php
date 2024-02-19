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
use Mollie\Payment\Service\Mollie\AvailableTerminals;
use Mollie\Payment\Service\Mollie\PointOfSaleAvailability;

class AvailableTerminalsForMethod implements ResolverInterface
{
    /**
     * @var PointOfSaleAvailability
     */
    private $pointOfSaleAvailability;
    /**
     * @var AvailableTerminals
     */
    private $availableTerminals;

    public function __construct(
        PointOfSaleAvailability $pointOfSaleAvailability,
        AvailableTerminals $availableTerminals
    ) {
        $this->pointOfSaleAvailability = $pointOfSaleAvailability;
        $this->availableTerminals = $availableTerminals;
    }

    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $method = $value['code'];
        if ($method != 'mollie_methods_pointofsale' || !$context->getExtensionAttributes()->getIsCustomer()) {
            return [];
        }

        $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();
        $customerGroupId = $context->getExtensionAttributes()->getCustomerGroupId();
        if (!$this->pointOfSaleAvailability->isAvailableForCustomerGroupId($customerGroupId, $storeId)) {
            return [];
        }

        return $this->availableTerminals->execute((int)$storeId);
    }
}
