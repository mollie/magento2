<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration;

use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Payment;
use stdClass;

class ExpressPaymentBuilder
{
    public function build(string $transactionId, ?string $streetAdditional = null): Payment
    {
        $payment = new Payment(new MollieApiClient());
        $payment->id = $transactionId;
        $payment->method = 'applepay';
        $payment->lines = [$this->productLine(), $this->shippingLine('5.00')];
        $payment->billingAddress = $this->address($streetAdditional);
        $payment->shippingAddress = $this->address($streetAdditional);
        $payment->_links = new stdClass();

        return $payment;
    }

    public function address(?string $streetAdditional = null): stdClass
    {
        $address = new stdClass();
        $address->givenName = 'John';
        $address->familyName = 'Doe';
        $address->streetAndNumber = 'Keizersgracht 126';
        $address->postalCode = '1015 CW';
        $address->city = 'Amsterdam';
        $address->country = 'NL';
        $address->phone = '0612345678';
        $address->email = 'aaa@aaa.com';

        if ($streetAdditional !== null) {
            $address->streetAdditional = $streetAdditional;
        }

        return $address;
    }

    public function productLine(string $sku = 'simple', string $name = 'Simple Product'): stdClass
    {
        $line = new stdClass();
        $line->type = 'physical';
        $line->description = '[' . $sku . '] ' . $name;
        $line->quantity = 1;
        $line->unitPrice = new stdClass();
        $line->unitPrice->value = '10.00';
        $line->totalAmount = new stdClass();
        $line->totalAmount->value = '10.00';

        return $line;
    }

    public function shippingLine(string $value): stdClass
    {
        $line = new stdClass();
        $line->type = 'shipping_fee';
        $line->description = 'Standard delivery';
        $line->quantity = 1;
        $line->unitPrice = new stdClass();
        $line->unitPrice->value = $value;
        $line->totalAmount = new stdClass();
        $line->totalAmount->value = $value;

        return $line;
    }
}
