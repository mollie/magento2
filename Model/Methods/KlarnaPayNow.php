<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model\Methods;

use Mollie\Payment\Model\Mollie;

/**
 * Legacy stub added in v3.0. The Mollie Orders API was removed in v3, which also removed the original
 * KlarnaPayNow method class. Without this stub, loading any pre-v3 order placed with this method
 * throws a LocalizedException because Magento cannot resolve the payment method instance.
 * This class is intentionally inactive (active=0 in config.xml) and must not be removed.
 */
class KlarnaPayNow extends Mollie
{
    public const CODE = 'mollie_methods_klarnapaynow';
}
