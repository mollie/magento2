<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Fakes\Stdlib;

use Magento\Framework\Stdlib\CookieManagerInterface;

class FakeCookieManager implements CookieManagerInterface
{
    /** @var array<string, string> */
    private array $cookies = [];

    public function setCookies(array $cookies): void
    {
        $this->cookies = $cookies;
    }

    public function clear(): void
    {
        $this->cookies = [];
    }

    public function getCookie($name, $default = null)
    {
        return $this->cookies[$name] ?? $default;
    }

    public function setSensitiveCookie($name, $value, $metadata = null) {}

    public function setPublicCookie($name, $value, $metadata = null) {}

    public function deleteCookie($name, $metadata = null) {}
}
