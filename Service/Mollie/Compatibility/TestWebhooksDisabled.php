<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 *  * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Mollie\Compatibility;

use Magento\Framework\App\State;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Adminhtml\Source\WebhookUrlOptions;

class TestWebhooksDisabled implements CompatibilityTestInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var State
     */
    private $appState;

    public function __construct(
        Config $config,
        State $appState
    ) {
        $this->config = $config;
        $this->appState = $appState;
    }

    public function execute(array $results)
    {
        if ($this->hasError()) {
            $msg = __('Warning: Webhooks are currently disabled.');
            $results[] = '<span class="mollie-error">' . $msg . '</span>';
        }

        return $results;
    }

    private function hasError()
    {
        if ($this->config->isProductionMode()) {
            return false;
        }

        return $this->config->useWebhooks() != WebhookUrlOptions::ENABLED;
    }
}