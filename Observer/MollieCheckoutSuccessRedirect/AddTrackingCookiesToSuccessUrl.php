<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Observer\MollieCheckoutSuccessRedirect;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Mollie\Payment\Config\TrackingCookies;

class AddTrackingCookiesToSuccessUrl implements ObserverInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly TrackingCookies $trackingCookiesConfig,
    ) {}

    public function execute(Observer $observer): void
    {
        $aliases = $this->trackingCookiesConfig->aliases();
        if (!$aliases) {
            return;
        }

        /** @var DataObject $redirect */
        $redirect = $observer->getData('redirect');
        $query = $redirect->getData('query') ?: [];

        foreach ($aliases as $alias) {
            $value = $this->request->getParam($alias);
            if ($value === null || $value === '') {
                continue;
            }

            $query[$alias] = $value;
        }

        $redirect->setData('query', $query);
    }
}
