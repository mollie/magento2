<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Adminhtml\Backend;


use Magento\Framework\App\Config\Value;

class FlushMollieCache extends Value
{
    public function beforeSave()
    {
        parent::beforeSave();

        if ($this->getOldValue() != $this->getValue()) {
            $this->flush();
        }

        return $this;
    }

    public function flush(): void
    {
        $this->_cacheManager->clean(['mollie_payment', 'mollie_payment_methods']);
    }
}
