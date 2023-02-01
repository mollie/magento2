<?php

namespace Mollie\Payment\GraphQL\Resolver\Cache;

use Magento\Framework\GraphQl\Query\Resolver\IdentityInterface;

class PaymentMethodsCache implements IdentityInterface
{
    public function getIdentities(array $resolvedData): array
    {
        return ['mollie_payment_methods'];
    }
}
