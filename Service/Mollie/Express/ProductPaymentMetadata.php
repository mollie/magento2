<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\Express;

use Magento\Framework\Exception\LocalizedException;
use stdClass;

/**
 * A product page session has no saved quote. Its metadata holds what the webhook and the shipping options callback
 * need to build the same quote again. Mollie returns the metadata only to us, so it can be trusted once fetched
 * from the API.
 */
class ProductPaymentMetadata
{
    private const PLACEMENT = 'product';
    private const KEY_PLACEMENT = 'placement';
    private const KEY_BUY_REQUEST = 'buyRequest';
    private const KEY_PAYMENT_TOKEN = 'paymentToken';
    private const KEY_STORE_ID = 'store_id';
    private const KEY_CUSTOMER_ID = 'customerId';

    public function __construct(
        public readonly ProductBuyRequest $buyRequest,
        public readonly string $paymentToken,
        public readonly int $storeId,
        public readonly ?int $customerId,
    ) {}

    public static function isProductPayment(mixed $metadata): bool
    {
        return $metadata instanceof stdClass
            && ($metadata->{self::KEY_PLACEMENT} ?? null) === self::PLACEMENT;
    }

    /**
     * @throws LocalizedException
     */
    public static function fromMollie(mixed $metadata): self
    {
        if (!self::isProductPayment($metadata)) {
            throw new LocalizedException(__('The Mollie session was not started from a product page.'));
        }

        $data = json_decode((string)json_encode($metadata), true);
        $customerId = $data[self::KEY_CUSTOMER_ID] ?? null;

        return new self(
            ProductBuyRequest::fromRequestParams((array)($data[self::KEY_BUY_REQUEST] ?? [])),
            (string)($data[self::KEY_PAYMENT_TOKEN] ?? ''),
            (int)($data[self::KEY_STORE_ID] ?? 0),
            $customerId === null ? null : (int)$customerId,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            self::KEY_PLACEMENT => self::PLACEMENT,
            self::KEY_BUY_REQUEST => $this->buyRequest->toArray(),
            self::KEY_PAYMENT_TOKEN => $this->paymentToken,
            self::KEY_STORE_ID => $this->storeId,
            self::KEY_CUSTOMER_ID => $this->customerId,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
