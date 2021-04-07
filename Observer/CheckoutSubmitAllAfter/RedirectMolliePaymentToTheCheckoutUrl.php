<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Observer\CheckoutSubmitAllAfter;


use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Mollie\Payment\Multishipping\CheckoutUrl;

class RedirectMolliePaymentToTheCheckoutUrl implements ObserverInterface
{
    /**
     * @var CheckoutUrl
     */
    private $checkoutUrl;

    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    public function __construct(
        CheckoutUrl $checkoutUrl,
        ResponseInterface $response,
        ResponseFactory $responseFactory
    ) {
        $this->checkoutUrl = $checkoutUrl;
        $this->response = $response;
        $this->responseFactory = $responseFactory;
    }

    public function execute(Observer $observer)
    {
        if ($url = $this->checkoutUrl->getUrl()) {
            $response = $this->responseFactory->create();
            $response->setRedirect($url);
            $response->sendResponse();

            // phpcs:ignore
            exit;
        }
    }
}
