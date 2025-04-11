<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Mollie\Order\Transaction;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class Expires
{
    /**
     * @var ScopeConfigInterface
     */
    private $config;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * @var RequestInterface
     */
    private $request;

    public function __construct(
        ScopeConfigInterface $config,
        TimezoneInterface $timezone,
        RequestInterface $request
    ) {
        $this->config = $config;
        $this->timezone = $timezone;
        $this->request = $request;
    }

    public function availableForMethod(?string $method = null, $storeId = null): bool
    {
        $value = $this->getExpiresAtForMethod($method, $storeId);
        return (bool)$value;
    }

    public function atDateForMethod(?string $method = null, $storeId = null): string
    {
        $days = $this->getExpiresAtForMethod($method, $storeId);

        if (strtotime($days)) {
            return $days;
        }

        $date = $this->timezone->scopeDate($storeId);
        $date->add(new \DateInterval('P' . $days . 'D'));

        return $date->format('Y-m-d');
    }

    /**
     * @param string $method
     * @param $storeId
     * @return mixed
     */
    public function getExpiresAtForMethod(?string $method = null, $storeId = null): ?string
    {
        if (!$method && $value = $this->getValueFromRequest()) {
            return $value;
        }

        $path = sprintf('payment/mollie_methods_%s/days_before_expire', $method);

        return $this->config->getValue($path, 'store', $storeId);
    }

    /**
     * @return mixed
     */
    private function getValueFromRequest(): ?string
    {
        $payment = $this->request->getParam('payment');

        if (!$payment || !isset($payment['days_before_expire'])) {
            return false;
        }

        return $payment['days_before_expire'];
    }
}
