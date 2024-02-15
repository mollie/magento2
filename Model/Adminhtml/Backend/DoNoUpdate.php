<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model\Adminhtml\Backend;

use Magento\Framework\App\Config\Value;

class DoNoUpdate extends Value
{
    public function save(): self
    {
        return $this;
    }
}
