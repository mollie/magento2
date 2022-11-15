<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\PaymentToken;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Api\PaymentTokenRepositoryInterface;

class PaymentTokenForOrder
{
    /**
     * @var PaymentTokenRepositoryInterface
     */
    private $paymentTokenRepository;

    /**
     * @var Generate
     */
    private $generate;

    public function __construct(
        PaymentTokenRepositoryInterface $paymentTokenRepository,
        Generate $generate
    ) {
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->generate = $generate;
    }

    public function execute(OrderInterface $order): string
    {
        if ($token = $this->paymentTokenRepository->getByOrder($order)) {
            return $token->getToken();
        }

        return $this->generate->forOrder($order)->getToken();
    }
}
