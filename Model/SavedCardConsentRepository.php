<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model;

use Magento\Framework\Exception\CouldNotSaveException;
use Mollie\Payment\Model\ResourceModel\SavedCardConsent as SavedCardConsentResource;

class SavedCardConsentRepository
{
    public function __construct(
        private SavedCardConsentResource $resource,
    ) {}

    /**
     * @throws CouldNotSaveException
     */
    public function save(SavedCardConsent $consent): SavedCardConsent
    {
        try {
            $this->resource->save($consent);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the saved card consent: %1',
                $exception->getMessage(),
            ));
        }

        return $consent;
    }
}
