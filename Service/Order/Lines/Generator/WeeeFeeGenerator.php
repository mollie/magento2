<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order\Lines\Generator;

use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Mollie\Payment\Helper\General;

class WeeeFeeGenerator implements GeneratorInterface
{
    private ?bool $forceBaseCurrency = null;

    /**
     * @var string|null
     */
    private $currency;

    public function __construct(
        private General $mollieHelper,
        private SerializerInterface $serializer
    ) {}

    public function process(OrderInterface $order, array $orderLines): array
    {
        $this->forceBaseCurrency = (bool) $this->mollieHelper->useBaseCurrency(storeId($order->getStoreId()));
        $this->currency = $this->forceBaseCurrency ? $order->getBaseCurrencyCode() : $order->getOrderCurrencyCode();

        if ($orderLine = $this->getWeeeFeeOrderLine($order)) {
            $orderLines[] = $orderLine;
        }

        return $orderLines;
    }

    private function getWeeeFeeOrderLine(OrderInterface $order): ?array
    {
        $total = 0.0;
        $weeeItems = $this->getWeeeItems($order);
        if (!$weeeItems) {
            return null;
        }

        /** @var OrderItemInterface $item */
        foreach ($weeeItems as $item) {
            $total += $this->getWeeeAmountForItem($item);
        }

        if (abs($total) < 0.01) {
            return null;
        }

        return [
            'type' => 'surcharge',
            'description' => $this->getTitle($weeeItems),
            'quantity' => 1,
            'unitPrice' => $this->mollieHelper->getAmountArray($this->currency, $total),
            'totalAmount' => $this->mollieHelper->getAmountArray($this->currency, $total),
            'vatRate' => 0,
            'vatAmount' => $this->mollieHelper->getAmountArray($this->currency, 0.0),
        ];
    }

    private function getWeeeItems(OrderInterface $order): array
    {
        return array_filter($order->getItems(), function (OrderItemInterface $item) {
            return $item->getWeeeTaxAppliedAmount();
        });
    }

    private function getTitle(array $items): string
    {
        /** @var OrderItemInterface $item */
        foreach ($items as $item) {
            $json = $this->serializer->unserialize($item->getWeeeTaxApplied());

            if (!$json) {
                continue;
            }

            foreach ($json as $applied) {
                if (isset($applied['title'])) {
                    return $applied['title'];
                }
            }
        }

        return 'FPT';
    }

    private function getWeeeAmountForItem(OrderItemInterface $item): float
    {
        $total = 0.0;
        $json = $this->serializer->unserialize($item->getWeeeTaxApplied());
        foreach ($json as $item) {
            $amount = $item['row_amount_incl_tax'];
            if ($this->forceBaseCurrency) {
                $amount = $item['base_row_amount_incl_tax'];
            }

            $total += $amount;
        }

        return $total;
    }
}
