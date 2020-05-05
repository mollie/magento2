<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Plugin\Sales\Block\Adminhtml\Order\Buttons;

use Magento\Framework\UrlInterface;
use Magento\OfflinePayments\Model\Checkmo;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Block\Adminhtml\Order\View;
use Magento\Sales\Helper\Reorder;
use Magento\Sales\Model\Order\Reorder\UnavailableProductsProvider;
use Mollie\Payment\Config;

class MarkAsPaidButton implements ButtonInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var PaymentHelper
     */
    private $paymentHelper;

    /**
     * @var Reorder
     */
    private $reorderHelper;

    /**
     * @var UnavailableProductsProvider
     */
    private $unavailableProductsProvider;

    public function __construct(
        Config $config,
        UrlInterface $url,
        OrderRepositoryInterface $orderRepository,
        PaymentHelper $paymentHelper,
        Reorder $reorderHelper,
        UnavailableProductsProvider $unavailableProductsProvider
    ) {
        $this->config = $config;
        $this->url = $url;
        $this->orderRepository = $orderRepository;
        $this->paymentHelper = $paymentHelper;
        $this->reorderHelper = $reorderHelper;
        $this->unavailableProductsProvider = $unavailableProductsProvider;
    }

    /**
     * @inheritDoc
     */
    public function add(View $view)
    {
        $order = $view->getOrder();
        if (!$this->config->paymentlinkAllowMarkAsPaid($order->getStoreId()) ||
            !$this->reorderHelper->canReorder($order->getId())
        ) {
            return;
        }

        $unavailableProducts = $this->unavailableProductsProvider->getForOrder($order);
        if (!$order->canCancel() ||
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
            -10
        );
    }
}
