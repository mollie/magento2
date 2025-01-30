<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\SelfTests;

use Magento\Framework\Module\Manager;

class AreHyvaModulesInstalled extends AbstractSelfTest
{
    /**
     * @var Manager
     */
    private $moduleManager;

    public function __construct(
        Manager $moduleManager
    ) {
        $this->moduleManager = $moduleManager;
    }

    public function execute(): void
    {
        if (!$this->moduleManager->isEnabled('Hyva_Theme')) {
            return;
        }

        if (!$this->moduleManager->isEnabled('Mollie_HyvaCompatibility')) {
            $this->addMessage('error', __('The <a href="https://github.com/mollie/magento2-hyva-compatibility" target="_blank">Mollie Hyvä Compatibility</a> module is not installed. Please install this module to use Mollie with the Hyvä Theme.'));
        }

        if ($this->moduleManager->isEnabled('Hyva_Checkout') &&
            !$this->moduleManager->isEnabled('Mollie_HyvaCheckout')
        ) {
            $this->addMessage('error', __('You have installed the Hyvä Checkout module, but not the Mollie Hyvä Checkout module. Please install this module to use Mollie with the Hyvä Checkout.'));
        }
    }
}
