<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Observer\ShortcutButtonsContainer;

use Magento\Catalog\Block\ShortcutButtons;
use Magento\Checkout\Block\QuoteShortcutButtons;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Mollie\Payment\Block\Applepay\Shortcut\Button;
use Mollie\Payment\Config;

class AddApplePayShortcutButton implements ObserverInterface
{
    public function __construct(
        private Config $config
    ) {}

    public function execute(Observer $observer): void
    {
        // We only want to show this button in the minicart
        if ($observer->getData('is_catalog_product') || !$this->isEnabled()) {
            return;
        }

        /** @var ShortcutButtons $shortcutButtons */
        $shortcutButtons = $observer->getEvent()->getContainer();
        $shortcut = $shortcutButtons->getLayout()->createBlock(Button::class);
        $shortcut->setIsCart(get_class($shortcutButtons) === QuoteShortcutButtons::class);
        $shortcutButtons->addShortcut($shortcut);
    }

    private function isEnabled(): bool
    {
        return $this->config->isMethodActive('mollie_methods_applepay') &&
            $this->config->applePayEnableMinicartButton();
    }
}
