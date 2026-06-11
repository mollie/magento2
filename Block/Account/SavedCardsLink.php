<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Block\Account;

use Magento\Customer\Block\Account\SortLink;
use Magento\Framework\App\DefaultPathInterface;
use Magento\Framework\View\Element\Template\Context;
use Mollie\Payment\Config;

class SavedCardsLink extends SortLink
{
    public function __construct(
        Context $context,
        DefaultPathInterface $defaultPath,
        private readonly Config $config,
        array $data = [],
    ) {
        parent::__construct($context, $defaultPath, $data);
    }

    protected function _toHtml(): string
    {
        if (!$this->config->creditcardEnableCustomersApi() || !$this->config->isProductionMode()) {
            return '';
        }

        return parent::_toHtml();
    }
}
