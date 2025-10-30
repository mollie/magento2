<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Fakes\Service\Mollie\Order;

use Mollie\Api\MollieApiClient;
use Mollie\Payment\Service\Mollie\Order\RefundUsingPayment;

class RefundUsingPaymentFake extends RefundUsingPayment
{
    private $calls = [];
    private $disableParentCall = false;


    public function disableParentCall(): void
    {
        $this->disableParentCall = true;
    }

    public function getCalls(): array
    {
        return $this->calls;
    }

    public function execute(MollieApiClient $mollieApi, $transactionId, $currencyCode, $amount)
    {
        $this->calls[] = [
            'transactionId' => $transactionId,
            'currencyCode' => $currencyCode,
            'amount' => $amount,
        ];

        if ($this->disableParentCall) {
            return;
        }

        parent::execute($mollieApi, $transactionId, $currencyCode, $amount);
    }
}
