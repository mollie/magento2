<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Quote;

use Magento\Framework\App\RequestInterface;

class GetApplePayShippingMethodIdentifier
{
    private const PARAM_SHIPPING_METHOD = 'shippingMethod';
    private const KEY_IDENTIFIER = 'identifier';

    public function execute(RequestInterface $request): ?string
    {
        $shippingMethod = $request->getParam(self::PARAM_SHIPPING_METHOD);
        if (!is_array($shippingMethod)) {
            return null;
        }

        $identifier = $shippingMethod[self::KEY_IDENTIFIER] ?? null;
        if (!is_string($identifier) || $identifier === '') {
            return null;
        }

        return $identifier;
    }
}
