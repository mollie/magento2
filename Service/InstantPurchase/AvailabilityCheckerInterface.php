<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\InstantPurchase\PaymentMethodIntegration;

/**
 * Dirty fix to check if InstantPurchase is available as it sometimes is replaced:
 * https://github.com/mollie/magento2/issues/470
 */
if (!interface_exists(\Magento\InstantPurchase\PaymentMethodIntegration\AvailabilityCheckerInterface::class)) {
    interface AvailabilityCheckerInterface
    {
    }
}
