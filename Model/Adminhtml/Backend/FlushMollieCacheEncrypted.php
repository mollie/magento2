<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Adminhtml\Backend;

use Magento\Config\Model\Config\Backend\Encrypted;

class FlushMollieCacheEncrypted extends Encrypted
{
    public function beforeSave()
    {
        parent::beforeSave();

        if ($this->getOldValue() != $this->getValue()) {
            $this->_cacheManager->clean(['mollie_payment', 'mollie_payment_methods']);
        }

        return $this;
    }
}
