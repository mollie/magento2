<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Plugin\Sales\Block\Adminhtml\Order\Buttons;

use Magento\Sales\Block\Adminhtml\Order\View;

interface ButtonInterface
{
    public function add(View $view): void;
}
