<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\Order\Transaction;

use DateInterval;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class Expires
{
    public function __construct(
        private ScopeConfigInterface $config,
        private TimezoneInterface $timezone,
        private RequestInterface $request
    ) {}

    public function availableForMethod(?string $method = null, $storeId = null): bool
    {
        $value = $this->getExpiresAtForMethod($method, $storeId);

        return (bool) $value;
    }

    public function atDateForMethod(?string $method = null, $storeId = null): string
    {
        $days = $this->getExpiresAtForMethod($method, $storeId);

        if (strtotime($days)) {
            return $days;
        }

        $date = $this->timezone->scopeDate($storeId);
        $date->add(new DateInterval('P' . $days . 'D'));

        return $date->format('Y-m-d');
    }

    /**
     * @param string|null $method
     * @param int|string|null $storeId
     * @return string|null
     */
    public function getExpiresAtForMethod(?string $method = null, $storeId = null): ?string
    {
        if (!$method && $value = $this->getValueFromRequest()) {
            return $value;
        }

        $path = sprintf('payment/mollie_methods_%s/days_before_expire', $method);

        return $this->config->getValue($path, 'store', $storeId);
    }

    private function getValueFromRequest(): ?string
    {
        $payment = $this->request->getParam('payment');

        if (!$payment || !isset($payment['days_before_expire'])) {
            return null;
        }

        return (string)$payment['days_before_expire'];
    }
}
