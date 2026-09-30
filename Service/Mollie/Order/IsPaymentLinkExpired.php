<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\Order;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Config;

class IsPaymentLinkExpired
{
    private const TIMEZONE = 'UTC';

    public function __construct(
        private readonly Config $config,
        private readonly DateTime $dateTime,
    ) {}

    public function execute(OrderInterface $order): bool
    {
        return $this->getExpiresAt($order) <= $this->now();
    }

    public function getExpiresAt(OrderInterface $order): DateTimeImmutable
    {
        return $this->utcDate((string) $order->getCreatedAt())
            ->add($this->getValidityPeriod(storeId($order->getStoreId())));
    }

    public function getLatestExpiredCreationDate(int $storeId): DateTimeImmutable
    {
        return $this->now()->sub($this->getValidityPeriod($storeId));
    }

    private function getValidityPeriod(?int $storeId): DateInterval
    {
        return new DateInterval(sprintf('P%dD', $this->config->paymentLinkDaysBeforeExpire($storeId)));
    }

    private function now(): DateTimeImmutable
    {
        return $this->utcDate($this->dateTime->gmtDate());
    }

    private function utcDate(string $date): DateTimeImmutable
    {
        return new DateTimeImmutable($date, new DateTimeZone(self::TIMEZONE));
    }
}
