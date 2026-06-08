<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Plugin\Sales\Block\Adminhtml\Order;

use Magento\Sales\Block\Adminhtml\Order\View as Subject;
use Mollie\Payment\Plugin\Sales\Block\Adminhtml\Order\Buttons\ButtonInterface;

class View
{
    /**
     * @param ButtonInterface[] $buttons
     */
    public function __construct(
        /**
         * @var ButtonInterface[]
         */
        private $buttons = [],
    ) {
    }

    public function beforeSetLayout(Subject $subject): void
    {
        foreach ($this->buttons as $button) {
            $button->add($subject);
        }
    }
}
