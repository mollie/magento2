<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order;

use Magento\Framework\UrlInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Adminhtml\Source\RedirectUserWhenTransactionFails;

class RedirectOnError
{
    public function __construct(
        private Config $config,
        private UrlInterface $urlBuilder
    ) {}

    public function getUrl(): string
    {
        $redirectTo = $this->config->redirectWhenTransactionFailsTo();
        if ($redirectTo == RedirectUserWhenTransactionFails::REDIRECT_TO_CHECKOUT_SHIPPING) {
            return $this->urlBuilder->getUrl('checkout');
        }

        if ($redirectTo == RedirectUserWhenTransactionFails::REDIRECT_TO_CHECKOUT_PAYMENT) {
            return $this->urlBuilder->getUrl('checkout') . '#payment';
        }

        // Always redirect to the cart, independent of the value.
        return $this->urlBuilder->getUrl('checkout/cart');
    }
}
