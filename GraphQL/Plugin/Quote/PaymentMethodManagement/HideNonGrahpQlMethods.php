<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\GraphQL\Plugin\Quote\PaymentMethodManagement;

use Magento\Quote\Api\PaymentMethodManagementInterface;
use Mollie\Payment\Model\Methods\CreditcardVault;

class HideNonGrahpQlMethods
{
    public function afterGetList(PaymentMethodManagementInterface $subject, array $result): array
    {
        return array_filter($result, function ($method): bool {
            return !$method instanceof CreditcardVault;
        });
    }
}
