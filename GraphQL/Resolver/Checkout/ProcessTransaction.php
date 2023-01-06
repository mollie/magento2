<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\GraphQL\Resolver\Checkout;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Api\CartRepositoryInterface;
use Mollie\Api\Types\PaymentStatus;
use Mollie\Payment\Api\PaymentTokenRepositoryInterface;
use Mollie\Payment\Model\Mollie;
use Mollie\Payment\Service\Mollie\ShouldRedirectToSuccessPage;

class ProcessTransaction implements ResolverInterface
{
    /**
     * @var Mollie
     */
    private $mollie;

    /**
     * @var PaymentTokenRepositoryInterface
     */
    private $paymentTokenRepository;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var ShouldRedirectToSuccessPage
     */
    private $shouldRedirectToSuccessPage;

    public function __construct(
        Mollie $mollie,
        PaymentTokenRepositoryInterface $paymentTokenRepository,
        CartRepositoryInterface $cartRepository,
        ShouldRedirectToSuccessPage $shouldRedirectToSuccessPage
    ) {
        $this->mollie = $mollie;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->cartRepository = $cartRepository;
        $this->shouldRedirectToSuccessPage = $shouldRedirectToSuccessPage;
    }

    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($args['input']['payment_token'])) {
            throw new GraphQlInputException(__('Missing "payment_token" input argument'));
        }

        $token = $args['input']['payment_token'];
        $tokenModel = $this->paymentTokenRepository->getByToken($token);

        if (!$tokenModel) {
            throw new GraphQlNoSuchEntityException(__('No order found with token "%1"', $token));
        }

        $result = $this->mollie->processTransaction($tokenModel->getOrderId(), 'success', $token);
        $redirectToSuccessPage = $this->shouldRedirectToSuccessPage->execute($result);

        $cart = null;
        if ($tokenModel->getCartId()) {
            $cart = $this->getCart(!$redirectToSuccessPage, $tokenModel->getCartId());
        }

        return [
            'paymentStatus' => strtoupper($result['status']),
            'cart' => $cart,
            'redirect_to_cart' => !$redirectToSuccessPage,
            'redirect_to_success_page' => $redirectToSuccessPage,
        ];
    }

    private function getCart(bool $restoreCart, string $cartId): ?array
    {
        try {
            $cart = $this->cartRepository->get($cartId);

            if ($restoreCart) {
                $cart->setIsActive(1);
                $cart->setReservedOrderId(null);
                $this->cartRepository->save($cart);
            }

            return ['model' => $cart];
        } catch (NoSuchEntityException $exception) {
            return null;
        }
    }
}
