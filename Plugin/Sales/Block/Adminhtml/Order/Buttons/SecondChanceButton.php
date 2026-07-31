<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Plugin\Sales\Block\Adminhtml\Order\Buttons;

use Magento\Framework\AuthorizationInterface;
use Magento\Framework\UrlInterface;
use Magento\Sales\Block\Adminhtml\Order\View;
use Magento\Sales\Model\Order;
use Mollie\Payment\Config;

class SecondChanceButton implements ButtonInterface
{
    public function __construct(
        private Config $config,
        private UrlInterface $url,
        private AuthorizationInterface $authorization,
    ) {}

    /**
     * @inheritDoc
     */
    public function add(View $view): void
    {
        if (!$this->authorization->isAllowed('Mollie_Payment::payment_reminders')) {
            return;
        }

        if (!$this->config->isSecondChanceEmailEnabled(storeId($view->getOrder()->getStoreId()))) {
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
                'onclick' => 'confirmSetLocation(\''
                    . __('Are you sure you want to send a payment reminder e-mail to the customer?')
                    . '\', \'' . $this->getUrl((string)$view->getOrderId()) . '\', {data: {}})',
            ],
        );
    }

    private function getUrl(string $orderId): string
    {
        return $this->url->getUrl('mollie/action/sendSecondChanceEmail/id/' . $orderId);
    }
}
