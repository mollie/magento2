<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Controller\Checkout;

use Magento\Framework\App\Action\Action;

/**
 * Class Success
 *
 * @package Mollie\Payment\Controller\Checkout
 */
class Success extends Action
{
    /**
     * @deprecated This code is moved to Process.php
     */
    public function execute()
    {
        $this->_forward('process');

        return $this->getResponse();
    }
}
