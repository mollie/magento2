<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model\Data;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Mollie\Payment\Config;

class CreditcardVaultConfig implements ConfigInterface
{
    public const DEFAULT_PATH_PATTERN = 'payment/%s/%s';

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param string|null $methodCode
     * @param string $pathPattern
     */
    public function __construct(
        private ScopeConfigInterface $scopeConfig,
        private ?string $methodCode = null,
        private string $pathPattern = self::DEFAULT_PATH_PATTERN
    ) {}

    /**
     * Sets method code
     *
     * @param string $methodCode
     * @return void
     */
    public function setMethodCode($methodCode): void
    {
        $this->methodCode = $methodCode;
    }

    /**
     * Sets path pattern
     *
     * @param string $pathPattern
     * @return void
     */
    public function setPathPattern($pathPattern): void
    {
        $this->pathPattern = $pathPattern;
    }

    /**
     * Retrieve information from payment configuration
     *
     * @param string $field
     * @param int|null $storeId
     *
     * @return mixed
     */
    public function getValue($field, $storeId = null)
    {
        if ($this->methodCode === null || $this->pathPattern === null) {
            return null;
        }

        $path = sprintf($this->pathPattern, $this->methodCode, $field);

        if ($field == 'active') {
            $path = Config::GENERAL_ENABLE_MAGENTO_VAULT;
        }

        return $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $storeId,
        );
    }
}
