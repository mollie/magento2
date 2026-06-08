<?php

declare(strict_types=1);

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Mollie\Parameters;

use Magento\Quote\Api\Data\CartInterface;

interface ParameterPartInterface
{
    /**
     * @param array $parameters
     * @param CartInterface $cart
     * @return array
     */
    public function enhance(array $parameters, CartInterface $cart): array;
}
