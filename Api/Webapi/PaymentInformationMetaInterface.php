<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Api\Webapi;

interface PaymentInformationMetaInterface
{
    /**
     * @return \Mollie\Payment\Api\Data\MethodMetaInterface[]
     */
    public function getPaymentMethodsMeta(): array;
}
