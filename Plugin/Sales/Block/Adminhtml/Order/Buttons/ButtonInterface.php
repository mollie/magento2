<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Plugin\Sales\Block\Adminhtml\Order\Buttons;

use Magento\Sales\Block\Adminhtml\Order\View;

interface ButtonInterface
{
    /**
     * @param View $view
     * @return void
     */
    public function add(View $view);
}
