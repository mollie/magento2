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
use Mollie\Payment\Service\Mollie\ExpressComponentsAvailability;

class AddExpressComponentsLayoutHandle implements ObserverInterface
{
    private const HANDLE = 'mollie_express_components';

    public function __construct(
        private readonly ExpressComponentsAvailability $availability
    ) {
    }

    public function execute(EventObserver $observer): void
    {
        if (!$this->availability->isAvailable()) {
            return;
        }

        /** @var LayoutInterface $layout */
        $layout = $observer->getEvent()->getData('layout');
        if ($layout === null) {
            return;
        }

        $layout->getUpdate()->addHandle(self::HANDLE);
    }
}
