<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Plugin\Sales\Block\Adminhtml\Order;

use Magento\Framework\UrlInterface;
use Magento\OfflinePayments\Model\Checkmo;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Block\Adminhtml\Order\View as Subject;
use Mollie\Payment\Config;
use Mollie\Payment\Plugin\Sales\Block\Adminhtml\Order\Buttons\ButtonInterface;

class View
{
    /**
     * @var ButtonInterface[]
     */
    private $buttons = [];

    public function __construct(
        $buttons = []
    ) {
        $this->buttons = $buttons;
    }

    public function beforeSetLayout(Subject $subject)
    {
        foreach ($this->buttons as $button) {
            $button->add($subject);
        }
    }
}
