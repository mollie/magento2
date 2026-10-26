<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Fakes\Api\Resources;

use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Payment;
use Mollie\Api\Resources\Refund;
use Mollie\Api\Resources\RefundCollection;
use stdClass;

class PaymentWithRefundsFake extends Payment
{
    /**
     * @var Refund[]
     */
    private array $fakeRefunds = [];

    public function __construct()
    {
        parent::__construct(new MollieApiClient());
    }

    public function withRefund(string $id, float $amount, string $currency = 'EUR'): self
    {
        $refund = new Refund($this->connector);
        $refund->id = $id;
        $refund->amount = new stdClass();
        $refund->amount->value = number_format($amount, 2, '.', '');
        $refund->amount->currency = $currency;

        $this->fakeRefunds[] = $refund;

        return $this;
    }

    public function hasRefunds(): bool
    {
        return $this->fakeRefunds !== [];
    }

    public function refunds(): RefundCollection
    {
        return new RefundCollection($this->connector, $this->fakeRefunds);
    }
}
