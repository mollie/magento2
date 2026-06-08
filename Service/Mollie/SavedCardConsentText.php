<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\Information;
use Magento\Store\Model\ScopeInterface;
use Mollie\Payment\Config;

class SavedCardConsentText
{
    public function __construct(
        private Config $config,
        private ScopeConfigInterface $scopeConfig,
    ) {}

    public function execute(?int $storeId = null): string
    {
        $template = $this->config->creditcardConsentText($storeId);

        $storeName = (string)$this->scopeConfig->getValue(
            Information::XML_PATH_STORE_INFO_NAME,
            ScopeInterface::SCOPE_STORE,
            $storeId,
        );

        $supportContact = (string)$this->scopeConfig->getValue(
            'trans_email/ident_general/email',
            ScopeInterface::SCOPE_STORE,
            $storeId,
        );

        $text = str_replace(
            ['{{tradingname}}', '{{supportcontact}}'],
            [$storeName, $supportContact],
            $template,
        );

        return preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2">$1</a>', $text);
    }
}
