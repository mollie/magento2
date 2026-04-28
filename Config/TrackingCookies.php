<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\ScopeInterface;

class TrackingCookies
{
    private const CONFIG_PATH = 'payment/mollie_general/tracking_cookies';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly SerializerInterface $serializer,
    ) {}

    /**
     * @return TrackingCookie[]
     */
    public function get(?int $storeId = null): array
    {
        $raw = $this->scopeConfig->getValue(
            self::CONFIG_PATH,
            ScopeInterface::SCOPE_STORE,
            $storeId,
        );

        if (!$raw) {
            return [];
        }

        try {
            $rows = is_array($raw) ? $raw : $this->serializer->unserialize($raw);
        } catch (\Throwable $exception) {
            return [];
        }

        if (!is_array($rows)) {
            return [];
        }

        $cookies = [];
        $seenAliases = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $cookieName = trim((string) ($row['cookie_name'] ?? ''));
            $alias = trim((string) ($row['alias'] ?? ''));
            if ($cookieName === '' || $alias === '' || isset($seenAliases[$alias])) {
                continue;
            }

            $cookies[] = new TrackingCookie($cookieName, $alias);
            $seenAliases[$alias] = true;
        }

        return $cookies;
    }

    /**
     * @return string[]
     */
    public function aliases(?int $storeId = null): array
    {
        return array_map(static fn (TrackingCookie $cookie) => $cookie->alias, $this->get($storeId));
    }
}
