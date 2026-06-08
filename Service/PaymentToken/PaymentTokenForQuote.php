<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\PaymentToken;

use Magento\Quote\Api\Data\CartInterface;
use Mollie\Payment\Api\PaymentTokenRepositoryInterface;

class PaymentTokenForQuote
{
    public function __construct(
        readonly private PaymentTokenRepositoryInterface $paymentTokenRepository,
        readonly private Generate $generate,
    ) {}

    public function execute(CartInterface $cart): string
    {
        $tokens = $this->paymentTokenRepository->getByCart($cart);
        if ($tokens->getTotalCount() > 0) {
            $items = $tokens->getItems();

            return array_shift($items)->getToken();
        }

        return $this->generate->forCart($cart);
    }
}
