<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Mollie\Wrapper;

use Mollie\Api\Factories\GetPaymentRequestFactory;
use Mollie\Api\Resources\Payment;
use Mollie\Payment\Service\Mollie\MollieApiClient;

class GetExpressPayment
{
    public function __construct(
        readonly private MollieApiClient $mollieApiClient,
    ) {}

    public function execute(int $storeId, string $paymentId): Payment
    {
        $mollieApi = $this->mollieApiClient->loadByStore($storeId);

        $paymentRequest = GetPaymentRequestFactory::new($paymentId)
            ->withQuery([])
            ->create();

        $paymentRequest->query()->add('include', 'details.idealExpressMetadata');

        return $mollieApi->send($paymentRequest);
    }
}
