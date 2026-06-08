<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Plugin\Sales\Block\Adminhtml\Order\Buttons;

use Magento\Framework\AuthorizationInterface;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Block\Adminhtml\Order\View;
use Magento\Sales\Helper\Reorder;
use Magento\Sales\Model\Order\Reorder\UnavailableProductsProvider;
use Mollie\Payment\Config;

class MarkAsPaidButton implements ButtonInterface
{
    public function __construct(
        private Config $config,
        private UrlInterface $url,
        private OrderRepositoryInterface $orderRepository,
        private PaymentHelper $paymentHelper,
        private Reorder $reorderHelper,
        private UnavailableProductsProvider $unavailableProductsProvider,
        private AuthorizationInterface $authorization,
    ) {}

    /**
     * @inheritDoc
     */
    public function add(View $view): void
    {
        if (!$this->authorization->isAllowed('Magento_Sales::actions')) {
            return;
        }

        $order = $view->getOrder();
        if (
            !$this->config->paymentlinkAllowMarkAsPaid(storeId($order->getStoreId())) ||
            !$this->reorderHelper->canReorder($order->getId())
        ) {
            return;
        }

        $unavailableProducts = $this->unavailableProductsProvider->getForOrder($order);
        if (
            !$order->canCancel() ||
            $order->getPayment()->getMethod() != 'mollie_methods_paymentlink' ||
            $unavailableProducts
        ) {
            return;
        }

        $message = __('Are you sure you want to do this? ' .
            'This will cancel the current order and create a new one that is marked as payed.');
        $url = $this->url->getUrl('mollie/action/markAsPaid/');

        $view->addButton(
            'mollie_payment_mark_as_payed',
            [
                'label' => __('Mark as paid'),
                'onclick' => 'confirmSetLocation(\'' . $message . '\', \'' . $url . '\', {data: {order_id: ' . $view->getOrderId() . '}})',
            ],
            0,
            -10,
        );
    }
}
