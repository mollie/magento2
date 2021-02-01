<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Plugin\Sales\Block\Adminhtml\Order\Buttons;

use Magento\Framework\UrlInterface;
use Magento\Sales\Block\Adminhtml\Order\View;
use Magento\Sales\Model\Order;
use Mollie\Payment\Config;

class SecondChanceButton implements ButtonInterface
{
    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @var Config
     */
    private $config;

    public function __construct(
        Config $config,
        UrlInterface $url
    ) {
        $this->url = $url;
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public function add(View $view)
    {
        if (!$this->config->isSecondChanceEmailEnabled($view->getOrder()->getStoreId())) {
            return;
        }

        $state = $view->getOrder()->getState();
        if (!in_array($state, [Order::STATE_NEW, Order::STATE_PENDING_PAYMENT, Order::STATE_CANCELED])) {
            return;
        }

        $view->addButton(
            'mollie_payment_second_chance_email',
            [
                'label' => __('Send Payment Reminder'),
                'onclick' => 'setLocation("' . $this->getUrl($view->getOrderId()) . '")',
            ]
        );
    }

    private function getUrl($orderId)
    {
        return $this->url->getUrl('mollie/action/sendSecondChanceEmail/id/' . $orderId);
    }
}
