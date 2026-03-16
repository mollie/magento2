<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Controller\Express;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;

class CreateSession implements HttpPostActionInterface
{
    public function __construct(
        readonly private Session $checkoutSession,
        readonly private JsonFactory $jsonFactory,
        readonly private CartRepositoryInterface $cartRepository,
        readonly private RequestInterface $request,
        readonly private \Mollie\Payment\Service\Mollie\CreateSession $createSession,
    ) {}

    public function execute()
    {
        $cart = $this->checkoutSession->getQuote();
        $this->setEmailOnCart($cart);

        $accessToken = $this->createSession->execute($cart);

        $cart->collectTotals();
        $this->cartRepository->save($cart);

        return $this->jsonFactory->create()->setData([
            'clientAccessToken' => $accessToken,
        ]);
    }

    private function setEmailOnCart(CartInterface|Quote $cart): void {
        $email = $this->request->getParam('email');

        if (!$email) {
            return;
        }

        $cart->getPayment()->setAdditionalInformation('mollie_guest_email', $email);
    }
}
