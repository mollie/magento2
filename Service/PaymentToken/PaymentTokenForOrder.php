<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\PaymentToken;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Api\PaymentTokenRepositoryInterface;

class PaymentTokenForOrder
{
    public function __construct(
        private PaymentTokenRepositoryInterface $paymentTokenRepository,
        private Generate $generate
    ) {}

    public function execute(OrderInterface $order): string
    {
        if ($token = $this->paymentTokenRepository->getByOrder($order)) {
            return $token->getToken();
        }

        return $this->generate->forOrder($order)->getToken();
    }
}
