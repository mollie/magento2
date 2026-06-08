<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Mollie\Api;

use Mollie\Api\Contracts\HasPayload;
use Mollie\Api\Http\Data\Money;
use Mollie\Api\Http\Requests\ResourceHydratableRequest;
use Mollie\Api\Resources\Session;
use Mollie\Api\Traits\HasJsonPayload;
use Mollie\Api\Types\Method;

class CreateSessionRequest extends ResourceHydratableRequest implements HasPayload
{
    use HasJsonPayload;

    protected static string $method = Method::POST;

    protected $hydratableResource = Session::class;

    public function __construct(
        readonly private string $redirectUrl,
        readonly private string $cancelUrl,
        readonly private Money $amount,
        readonly private string $description,
        readonly private array $lines,
        readonly private array $payment = [],
        readonly private array $metadata = [],
    ) {}

    public function resolveResourcePath(): string
    {
        return 'sessions';
    }

    protected function defaultPayload(): array
    {
        $output = [
            'amount' => $this->amount,
            'description' => $this->description,
            'redirectUrl' => $this->redirectUrl,
            'cancelUrl' => $this->cancelUrl,
            'lines' => $this->lines,
        ];

        if ($this->payment !== []) {
            $output['payment'] = $this->payment;
        }

        if ($this->metadata !== []) {
            $output['metadata'] = $this->metadata;
        }

        return $output;
    }
}
