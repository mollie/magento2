<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie;

use Magento\Quote\Api\Data\CartInterface;
use Mollie\Payment\Service\Mollie\Parameters\ParameterPartInterface;

class MethodParameters
{
    /**
     * @param ParameterPartInterface[] $parametersParts
     */
    public function __construct(
        private array $parametersParts
    ) {}

    public function enhance(array $parameters, CartInterface $cart): array
    {
        foreach ($this->parametersParts as $parametersPart) {
            $parameters = $parametersPart->enhance($parameters, $cart);
        }

        return $parameters;
    }
}
