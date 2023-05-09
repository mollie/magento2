<?php

namespace Mollie\Payment\Service\Mollie\Order;

use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Methods\ApplePay;
use Mollie\Payment\Model\Methods\Creditcard;
use Mollie\Payment\Model\Methods\Directdebit;
use Mollie\Payment\Model\Mollie;

class RedirectUrl
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
     * @var ManagerInterface
     */
    private $messageManager;

    public function __construct(
        Config $config,
        UrlInterface $url,
        ManagerInterface $messageManager
    ) {
        $this->config = $config;
        $this->url = $url;
        $this->messageManager = $messageManager;
    }

    public function execute(Mollie $methodInstance, OrderInterface $order): string
    {
        $redirectUrl = $methodInstance->startTransaction($order);

        /**
         * Directdebit does not return an url when in test mode.
         */
        if (!$redirectUrl && $methodInstance instanceof Directdebit && $this->config->isTestMode()) {
            return $this->url->getUrl('checkout/onepage/success/');
        }

        $emptyUrlAllowed = $methodInstance instanceof ApplePay || $methodInstance instanceof Creditcard;
        if (!$redirectUrl && $emptyUrlAllowed) {
            return $this->url->getUrl('checkout/onepage/success/');
        }

        if (!$redirectUrl) {
            $this->config->addToLog(
                'error',
                'RedirectUrl: No redirect url found for order ' . $order->getIncrementId()
            );

            $this->messageManager->addErrorMessage(
                __('Something went wrong while trying to redirect you to Mollie. Please try again later.')
            );

            return $this->url->getUrl('checkout/cart');
        }

        return $redirectUrl;
    }
}
