<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Mail\Template\SenderResolverInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address\Renderer;
use Magento\Sales\Model\Order\Email\Container\OrderIdentity;
use Magento\Sales\Model\Order\Email\Container\Template;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Email\SenderBuilderFactory;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Mollie\Payment\Config;
use Mollie\Payment\Service\Magento\PaymentLinkUrl;
use Mollie\Payment\Service\Order\Email\PaymentLinkOrderIdentity;
use Psr\Log\LoggerInterface;

class PaymentLinkConfirmationEmail extends OrderSender
{
    /**
     * @var PaymentLinkUrl
     */
    private $paymentLinkUrl;

    public function __construct(
        Template $templateContainer,
        SenderBuilderFactory $senderBuilderFactory,
        LoggerInterface $logger,
        Renderer $addressRenderer,
        PaymentHelper $paymentHelper,
        OrderResource $orderResource,
        ScopeConfigInterface $globalConfig,
        ManagerInterface $eventManager,
        PaymentLinkOrderIdentity $identityContainer,
        PaymentLinkUrl $paymentLinkUrl
    ) {
        parent::__construct(
            $templateContainer,
            $identityContainer,
            $senderBuilderFactory,
            $logger,
            $addressRenderer,
            $paymentHelper,
            $orderResource,
            $globalConfig,
            $eventManager
        );

        $this->paymentLinkUrl = $paymentLinkUrl;
    }

    protected function prepareTemplate(Order $order)
    {
        parent::prepareTemplate($order);

        $transportObject = new DataObject($this->templateContainer->getTemplateVars());
        $transportObject->setData('mollie_payment_link', $this->paymentLinkUrl->execute((int)$order->getEntityId()));

        $this->eventManager->dispatch(
            'mollie_email_paymenlink_order_set_template_vars_before',
            ['sender' => $this, 'transportObject' => $transportObject]
        );

        $this->templateContainer->setTemplateVars($transportObject->getData());
    }
}
