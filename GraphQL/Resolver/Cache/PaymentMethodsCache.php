<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\GraphQL\Resolver\Cache;

use Magento\Framework\GraphQl\Query\Resolver\IdentityInterface;

class PaymentMethodsCache implements IdentityInterface
{
    public function getIdentities(array $resolvedData): array
    {
        return ['mollie_payment_methods'];
    }
}
