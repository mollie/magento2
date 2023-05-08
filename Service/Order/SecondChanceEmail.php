<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Order;

use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Mail\Template\SenderResolverInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Email\Container\IdentityInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mollie\Payment\Api\PaymentTokenRepositoryInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Logger\MollieLogger;
use Mollie\Payment\Service\PaymentToken\Generate;

class SecondChanceEmail
{
    const UTM_TAG = '?utm_source=second_chance_email&utm_medium=mollie_second_chance&utm_campaign=second_chance_order';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var SenderResolverInterface
     */
    private $senderResolver;

    /**
     * @var TransportBuilder
     */
    private $transportBuilder;

    /**
     * @var IdentityInterface
     */
    private $identityContainer;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var PaymentTokenRepositoryInterface
     */
    private $paymentTokenRepository;

    /**
     * @var Generate
     */
    private $paymentToken;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @var MollieLogger
     */
    private $logger;

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    public function __construct(
        Config $config,
        SenderResolverInterface $senderResolver,
        TransportBuilder $transportBuilder,
        IdentityInterface $identityContainer,
        StoreManagerInterface $storeManager,
        PaymentTokenRepositoryInterface $paymentTokenRepository,
        Generate $paymentToken,
        UrlInterface $url,
        MollieLogger $logger,
        ManagerInterface $eventManager
    ) {
        $this->config = $config;
        $this->senderResolver = $senderResolver;
        $this->transportBuilder = $transportBuilder;
        $this->identityContainer = $identityContainer;
        $this->storeManager = $storeManager;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->paymentToken = $paymentToken;
        $this->url = $url;
        $this->logger = $logger;
        $this->eventManager = $eventManager;
    }

    public function send(OrderInterface $order)
    {
        $storeId = $order->getStoreId();
        $templateId = $this->config->secondChanceEmailTemplate($storeId);

        $customerName = $order->getCustomerName();
        if ($order->getCustomerIsGuest()) {
            $customerName = $order->getBillingAddress()->getName();
        }

        $builder = $this->transportBuilder->setTemplateIdentifier($templateId);
        $builder->setTemplateOptions(['area' => 'frontend', 'store' => $storeId]);
        $this->setFrom($builder, $storeId);
        $builder->addTo($order->getCustomerEmail(), $customerName);

        if ($bcc = $this->config->secondChanceSendBccTo($storeId)) {
            $builder->addBcc(explode(',', $bcc));
        }

        $templateVars = new DataObject($this->getTemplateVars($order));
        $this->eventManager->dispatch('mollie_second_change_email_before_send', ['variables' => $templateVars]);
        $builder->setTemplateVars($templateVars->toArray());

        $this->logger->addInfoLog(
            'info',
            sprintf('Sending second chance email for order #%s', $order->getIncrementId())
        );

        $transport = $builder->getTransport();
        $transport->sendMessage();

        $this->eventManager->dispatch('mollie_second_change_email_after_send', ['variables' => $templateVars]);

        $this->logger->addInfoLog('info', sprintf('Second chance email for order #%s sent', $order->getIncrementId()));
    }

    private function getTemplateVars(OrderInterface $order)
    {
        $token = $this->paymentTokenRepository->getByOrder($order);

        if (!$token) {
            $token = $this->paymentToken->forOrder($order);
        }

        return [
            'link' => $this->getUrl($order, $token),
            'customer' => [
                'name' => $order->getCustomerName(),
                'email' => $order->getCustomerEmail(),
            ],
            'order' => $order,
            'order_id' => $order->getEntityId(),
            'store' => $this->storeManager->getStore($order->getStoreId()),
            'payment_token' => $token->getToken(),
        ];
    }

    /**
     * @param OrderInterface $order
     * @param $token
     * @return string
     */
    private function getUrl(OrderInterface $order, $token)
    {
        return $this->url->getUrl('mollie/checkout/secondChance/', [
            '_scope' => $order->getStoreId(),
            'order_id' => $order->getEntityId(),
            'payment_token' => $token->getToken()
        ]) . static::UTM_TAG;
    }

    private function setFrom(TransportBuilder $builder, int $storeId)
    {
        $emailIdentity = $this->identityContainer->getEmailIdentity();

        // Only exists in newer versions
        // @see https://github.com/mollie/magento2/issues/367#issuecomment-805840292
        if (method_exists($builder, 'setFromByScope')) {
            $builder->setFromByScope($emailIdentity, $storeId);
            return;
        }

        $from = $this->senderResolver->resolve($emailIdentity, $storeId);
        $builder->setFrom($from);
    }
}
