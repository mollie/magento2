<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 *  * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Mollie\SelfTests;

use Mollie\Payment\Config;
use Mollie\Payment\Model\Adminhtml\Source\WebhookUrlOptions;

class TestWebhooksDisabled extends AbstractSelfTest
{
    /**
     * @var Config
     */
    private $config;

    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    public function execute(): void
    {
        if ($this->hasError()) {
            $message = __('Warning: Webhooks are currently disabled.');
            $this->addMessage('error', $message);
        }
    }

    private function hasError()
    {
        if ($this->config->isProductionMode()) {
            return false;
        }

        return $this->config->useWebhooks() != WebhookUrlOptions::ENABLED;
    }
}
