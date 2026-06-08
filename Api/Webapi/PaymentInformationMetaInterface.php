<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Api\Webapi;

interface PaymentInformationMetaInterface
{
    /**
     * @return \Mollie\Payment\Api\Data\MethodMetaInterface[]
     */
    public function getPaymentMethodsMeta(): array;
}
