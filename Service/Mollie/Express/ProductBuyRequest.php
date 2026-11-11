<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\Express;

use Magento\Framework\Exception\LocalizedException;

/**
 * The fields of the add to cart form that describe what the customer buys. It travels in the Mollie session
 * metadata, which is limited in size, so everything else of the form is left out.
 */
class ProductBuyRequest
{
    public const PRODUCT = 'product';
    public const QTY = 'qty';

    private const ALLOWED_FIELDS = [
        self::PRODUCT,
        self::QTY,
        'super_attribute',
        'options',
        'bundle_option',
        'bundle_option_qty',
        'super_group',
        'links',
    ];

    /**
     * @param array<string, mixed> $fields
     */
    private function __construct(
        private readonly array $fields,
    ) {}

    /**
     * @param array<string, mixed> $params
     * @throws LocalizedException
     */
    public static function fromRequestParams(array $params): self
    {
        $fields = array_intersect_key($params, array_flip(self::ALLOWED_FIELDS));
        if ((int)($fields[self::PRODUCT] ?? 0) === 0) {
            throw new LocalizedException(__('No product was provided for the express payment.'));
        }

        return new self($fields);
    }

    public function getProductId(): int
    {
        return (int)$this->fields[self::PRODUCT];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->fields;
    }
}
