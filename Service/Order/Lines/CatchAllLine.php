<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order\Lines;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Api\Types\OrderLineType;
use Mollie\Payment\Helper\General;
use Mollie\Payment\Logger\MollieLogger;

class CatchAllLine
{
    public const SKU = 'catch-all';

    private const VAT_RATE = 0.0;

    public function __construct(
        private readonly General $mollieHelper,
        private readonly MollieLogger $logger,
        private readonly ThirdPartyTotalCollectors $thirdPartyTotalCollectors,
    ) {}

    public function create(OrderInterface $order, ?string $currency, float $difference): array
    {
        $this->logger->addInfoLog(
            'Order lines do not match the grand total, added a catch-all line',
            [
                'increment_id' => $order->getIncrementId(),
                'difference' => $difference,
                'third_party_total_collectors' => $this->thirdPartyTotalCollectors->get(),
            ],
        );

        return [
            'item_id' => '',
            'type' => $this->isSurcharge($difference) ? OrderLineType::SURCHARGE : OrderLineType::DISCOUNT,
            'description' => $this->getDescription($difference),
            'quantity' => 1,
            'unitPrice' => $this->mollieHelper->getAmountArray($currency, $difference),
            'totalAmount' => $this->mollieHelper->getAmountArray($currency, $difference),
            'vatRate' => sprintf('%.2f', self::VAT_RATE),
            'vatAmount' => $this->mollieHelper->getAmountArray($currency, self::VAT_RATE),
            'sku' => self::SKU,
        ];
    }

    private function isSurcharge(float $difference): bool
    {
        return $difference > 0;
    }

    private function getDescription(float $difference): string
    {
        return $this->isSurcharge($difference) ? (string)__('Additional fees') : (string)__('Additional discount');
    }
}
