<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model;

use Magento\Framework\Model\AbstractModel;

class SavedCardConsent extends AbstractModel
{
    protected $_eventPrefix = 'mollie_saved_card_consent';

    protected function _construct()
    {
        $this->_init(ResourceModel\SavedCardConsent::class);
    }

    public function setOrderId(int $orderId): self
    {
        return $this->setData('order_id', $orderId);
    }

    public function setStoreId(int $storeId): self
    {
        return $this->setData('store_id', $storeId);
    }

    public function setConsentTimestamp(string $timestamp): self
    {
        return $this->setData('consent_timestamp', $timestamp);
    }
}
