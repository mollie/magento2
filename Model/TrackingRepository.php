<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model;

use Magento\Framework\Exception\CouldNotSaveException;
use Mollie\Payment\Api\Data\TrackingInterface;
use Mollie\Payment\Api\TrackingRepositoryInterface;
use Mollie\Payment\Model\ResourceModel\Tracking as TrackingResource;
use Mollie\Payment\Model\ResourceModel\Tracking\CollectionFactory;

class TrackingRepository implements TrackingRepositoryInterface
{
    public function __construct(
        private readonly TrackingResource $resource,
        private readonly TrackingFactory $trackingFactory,
        private readonly CollectionFactory $collectionFactory,
    ) {}

    public function save(TrackingInterface $tracking): TrackingInterface
    {
        if (!$tracking instanceof Tracking) {
            $model = $this->trackingFactory->create();
            $model->setData($tracking->getCartId() === null ? [] : [
                TrackingInterface::CART_ID => $tracking->getCartId(),
            ]);
            $model->setTrackingData($tracking->getTrackingData());
            $tracking = $model;
        }

        try {
            $this->resource->save($tracking);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the tracking record: %1',
                $exception->getMessage(),
            ));
        }

        return $tracking;
    }

    public function getByCartId(int $cartId): ?TrackingInterface
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(TrackingInterface::CART_ID, $cartId);
        $collection->setOrder(TrackingInterface::ENTITY_ID, 'DESC');
        $collection->setPageSize(1);

        $item = $collection->getFirstItem();

        return $item->getEntityId() ? $item : null;
    }

    public function getTrackingDataByCartId(int $cartId): array
    {
        $tracking = $this->getByCartId($cartId);

        return $tracking ? $tracking->getTrackingData() : [];
    }
}
