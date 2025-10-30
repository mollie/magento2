<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model\Adminhtml\Backend;

use Magento\Framework\App\Config\Value;

class VerifiyPaymentFee extends Value
{
    public function beforeSave()
    {
        $value = $this->getValue();
        $this->setValue(str_replace([',', '%'], ['.', ''], $value));

        return $this;
    }
}
