<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order\TransactionPart;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Service\Order\TransactionPartInterface;
use Mollie\Payment\Service\Tracking\CookieCollector;

class AddTrackingCookiesToRedirectUrl implements TransactionPartInterface
{
    public function __construct(
        private readonly CookieCollector $collector,
    ) {}

    public function process(OrderInterface $order, array $transaction): array
    {
        if (!isset($transaction['redirectUrl']) || $transaction['redirectUrl'] === '') {
            return $transaction;
        }

        $cookies = $this->collector->collect(
            $order->getStoreId() === null ? null : (int) $order->getStoreId(),
        );

        if (!$cookies) {
            return $transaction;
        }

        $url = (string) $transaction['redirectUrl'];
        foreach ($cookies as $alias => $value) {
            $separator = str_contains($url, '?') ? '&' : '?';
            $url .= $separator . rawurlencode($alias) . '=' . rawurlencode($value);
        }

        $transaction['redirectUrl'] = $url;

        return $transaction;
    }
}
