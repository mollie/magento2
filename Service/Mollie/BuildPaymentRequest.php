<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie;

use Mollie\Api\Http\Data\Address;
use Mollie\Api\Http\Data\AddressFactory;
use Mollie\Api\Http\Data\DataCollectionFactory;
use Mollie\Api\Http\Data\Money;
use Mollie\Api\Http\Data\MoneyFactory;
use Mollie\Api\Http\Requests\CreatePaymentRequest;
use Mollie\Api\Http\Requests\CreatePaymentRequestFactory;

class BuildPaymentRequest
{
    public function __construct(
        private CreatePaymentRequestFactory $createPaymentRequestFactory,
        private MoneyFactory $moneyFactory,
        private AddressFactory $addressFactory,
        private DataCollectionFactory $dataCollectionFactory
    ) {}

    public function execute(array $request): CreatePaymentRequest
    {
        $request['amount'] = $this->convertToMoney($request['amount']);
        $request['billingAddress'] = $this->convertToAddress($request['billingAddress']);
        $request['shippingAddress'] = $this->convertToAddress($request['shippingAddress']);

        if (array_key_exists('lines', $request)) {
            $request['lines'] = $this->dataCollectionFactory->create(['items' => $request['lines']]);
        }

        return $this->createPaymentRequestFactory->create($request);
    }

    private function convertToMoney(array $amount): Money
    {
        return $this->moneyFactory->create($amount);
    }

    private function convertToAddress(array $address): Address
    {
        return $this->addressFactory->create($address);
    }
}
