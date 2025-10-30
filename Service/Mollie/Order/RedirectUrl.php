<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\Order;

use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Methods\ApplePay;
use Mollie\Payment\Model\Methods\Creditcard;
use Mollie\Payment\Model\Methods\Directdebit;
use Mollie\Payment\Model\Methods\Pointofsale;
use Mollie\Payment\Model\Mollie;
use Mollie\Payment\Service\Mollie\StartTransaction;

class RedirectUrl
{
    public function __construct(
        private Config $config,
        private EncryptorInterface $encryptor,
        private UrlInterface $url,
        private ManagerInterface $messageManager,
        private StartTransaction $startTransaction
    ) {}

    public function execute(Mollie $methodInstance, OrderInterface $order): string
    {
        $redirectUrl = $this->startTransaction->execute($order);

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

        if ($methodInstance instanceof Pointofsale && !$redirectUrl) {
            return $this->url->getUrl(
                'mollie/checkout/pointofsale',
                ['token' => base64_encode($this->encryptor->encrypt((string) $order->getId()))],
            );
        }

        if (!$redirectUrl) {
            $this->config->addToLog(
                'error',
                'RedirectUrl: No redirect url found for order ' . $order->getIncrementId(),
            );

            $this->messageManager->addErrorMessage(
                __('Something went wrong while trying to redirect you to Mollie. Please try again later.'),
            );

            return $this->url->getUrl('checkout/cart');
        }

        return $redirectUrl;
    }
}
