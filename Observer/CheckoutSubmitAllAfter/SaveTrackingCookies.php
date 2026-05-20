<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Observer\CheckoutSubmitAllAfter;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Mollie\Payment\Api\TrackingRepositoryInterface;
use Mollie\Payment\Model\TrackingFactory;
use Mollie\Payment\Service\Tracking\CookieCollector;

class SaveTrackingCookies implements ObserverInterface
{
    public function __construct(
        private readonly CookieCollector $collector,
        private readonly TrackingFactory $trackingFactory,
        private readonly TrackingRepositoryInterface $repository,
    ) {}

    public function execute(Observer $observer): void
    {
        $quote = $observer->getData('quote');
        if (!$quote || !$quote->getEntityId()) {
            return;
        }

        $storeId = $quote->getStoreId() === null ? null : (int) $quote->getStoreId();
        $data = $this->collector->collect($storeId);
        if (!$data) {
            return;
        }

        $tracking = $this->trackingFactory->create();
        $tracking->setCartId((int) $quote->getEntityId());
        $tracking->setTrackingData($data);

        $this->repository->save($tracking);
    }
}
