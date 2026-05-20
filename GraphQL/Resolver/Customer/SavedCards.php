<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\GraphQL\Resolver\Customer;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Mollie\Payment\Config;
use Mollie\Payment\Service\Mollie\GetCustomerMandates;

class SavedCards implements ResolverInterface
{
    public function __construct(
        private GetCustomerMandates $getCustomerMandates,
        private Config $config,
    ) {}

    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null): array
    {
        if (!$context->getUserId()) {
            throw new GraphQlAuthorizationException(__('The current customer is not authorized.'));
        }

        $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();

        if (!$this->config->creditcardEnableCustomersApi($storeId)) {
            return [];
        }

        try {
            return $this->getCustomerMandates->execute((int)$context->getUserId(), $storeId);
        } catch (LocalizedException $e) {
            throw new \Magento\Framework\GraphQl\Exception\GraphQlInputException(__($e->getMessage()));
        }
    }
}
