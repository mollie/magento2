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

class View
{
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

    public function __construct(
        UrlInterface $url,
        OrderRepositoryInterface $orderRepository,
        PaymentHelper $paymentHelper
    ) {
        $this->url = $url;
        $this->orderRepository = $orderRepository;
        $this->paymentHelper = $paymentHelper;
    }

    public function beforeSetLayout(Subject $subject)
    {
        $order = $subject->getOrder();
        $instance = $this->paymentHelper->getMethodInstance(Checkmo::PAYMENT_METHOD_CHECKMO_CODE);

        $isAvailable = !$instance->isAvailable();
        if (!$order->canCancel() ||
            $order->getPayment()->getMethod() != 'mollie_methods_paymentlink' ||
            !$instance ||
            $isAvailable
        ) {
            return;
        }

        $message = __('Are you sure you want to do this? ' .
            'This will cancel the current order and create a new one that is marked as payed.');
        $url = $this->url->getUrl('mollie/action/markAsPaid/');

        $subject->addButton(
            'mollie_payment_mark_as_payed',
            [
                'label' => __('Mark as paid'),
                'onclick' => 'confirmSetLocation(\'' . $message . '\', \'' . $url . '\', {data: {order_id: ' . $subject->getOrderId() . '}})',
            ],
            0,
            -10
        );
    }
}
