<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Observer\LayoutLoadBefore;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\LayoutInterface;
use Mollie\Payment\Service\Mollie\Express\ExpressComponentsBeta;
use Mollie\Payment\Service\Mollie\ExpressComponentsAvailability;

class AddExpressComponentsLayoutHandle implements ObserverInterface
{
    private const HANDLE = 'mollie_express_components';
    private const BETA_PRODUCT_HANDLE = 'mollie_express_components_beta_product';
    private const BETA_SHIPPING_HANDLE = 'mollie_express_components_beta_shipping';
    private const BETA_MINICART_HANDLE = 'mollie_express_components_beta_minicart';
    private const MINICART_DISABLED_HANDLE = 'mollie_express_components_minicart_disabled';

    public function __construct(
        private readonly ExpressComponentsAvailability $availability,
        private readonly ExpressComponentsBeta $beta,
    ) {
    }

    public function execute(EventObserver $observer): void
    {
        if (!$this->availability->isAvailable()) {
            return;
        }

        /** @var LayoutInterface|null $layout */
        $layout = $observer->getEvent()->getData('layout');
        if ($layout === null) {
            return;
        }

        $update = $layout->getUpdate();
        $update->addHandle(self::HANDLE);

        if (!$this->beta->isEnabled()) {
            return;
        }

        if ($this->beta->shouldShowOnProductPage()) {
            $update->addHandle(self::BETA_PRODUCT_HANDLE);
        }

        if ($this->beta->shouldShowOnShippingStep()) {
            $update->addHandle(self::BETA_SHIPPING_HANDLE);
        }

        $update->addHandle(
            $this->beta->shouldShowInMinicart() ? self::BETA_MINICART_HANDLE : self::MINICART_DISABLED_HANDLE
        );
    }
}
