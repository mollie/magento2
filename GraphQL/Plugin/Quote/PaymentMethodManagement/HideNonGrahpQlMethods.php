<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\GraphQL\Plugin\Quote\PaymentMethodManagement;

use Magento\Quote\Api\PaymentMethodManagementInterface;
use Mollie\Payment\Model\Methods\CreditcardVault;

class HideNonGrahpQlMethods
{
    public function afterGetList(PaymentMethodManagementInterface $subject, array $result)
    {
        return array_filter($result, function ($method) {
            return !$method instanceof CreditcardVault;
        });
    }
}
