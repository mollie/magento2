<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InstantPurchase\PaymentMethodIntegration;

/**
 * Dirty fix to check if InstantPurchase is available as it sometimes is replaced:
 * https://github.com/mollie/magento2/issues/470
 */

if (!interface_exists(AvailabilityCheckerInterface::class)) {
    interface AvailabilityCheckerInterface
    {
    }
}
