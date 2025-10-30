<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\Wrapper;

trait ApiKeyFallbackTrait
{
    private $fallbackApiKeys = [];

    private $instanceInitiated = false;

    /**
     * @var FetchFallbackApiKeys
     */
    private $fallbackApiKeysInstance;

    /**
     * This instance is a proxy, so don't call it until really needed.
     *
     * @param FetchFallbackApiKeys $fetchFallbackApiKeys
     * @return void
     */
    public function setFallbackApiKeysInstance(FetchFallbackApiKeys $fetchFallbackApiKeys): void
    {
        $this->fallbackApiKeysInstance = $fetchFallbackApiKeys;
    }

    public function updateClient(): bool
    {
        $this->loadApiKeys();
        $fallbackKey = array_shift($this->fallbackApiKeys);
        if (!$fallbackKey) {
            return false;
        }

        $this->setApiKey($fallbackKey);

        return true;
    }

    private function loadApiKeys(): void
    {
        if ($this->instanceInitiated) {
            return;
        }

        $this->instanceInitiated = true;
        $this->fallbackApiKeys = $this->fallbackApiKeysInstance->retrieve();
    }
}
