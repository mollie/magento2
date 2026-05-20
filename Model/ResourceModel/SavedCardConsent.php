<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class SavedCardConsent extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('mollie_saved_card_consent', 'entity_id');
    }
}
