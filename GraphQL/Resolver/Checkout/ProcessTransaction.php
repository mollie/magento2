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
use Magento\Sales\Api\OrderRepositoryInterface;
use Mollie\Payment\Api\PaymentTokenRepositoryInterface;
use Mollie\Payment\Service\Mollie\ProcessTransaction as ProcessTransactionAction;

class ProcessTransaction implements ResolverInterface
{
    /**
     * @var PaymentTokenRepositoryInterface
     */
    private $paymentTokenRepository;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var ProcessTransactionAction
     */
    private $processTransaction;
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    public function __construct(
        PaymentTokenRepositoryInterface $paymentTokenRepository,
        CartRepositoryInterface $cartRepository,
        ProcessTransactionAction $processTransaction,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->cartRepository = $cartRepository;
        $this->processTransaction = $processTransaction;
        $this->orderRepository = $orderRepository;
    }

    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        if (!isset($args['input']['payment_token'])) {
            throw new GraphQlInputException(__('Missing "payment_token" input argument'));
        }

        $token = $args['input']['payment_token'];
        $tokenModel = $this->paymentTokenRepository->getByToken($token);

        if (!$tokenModel) {
            throw new GraphQlNoSuchEntityException(__('No order found with token "%1"', $token));
        }

        $order = $this->orderRepository->get($tokenModel->getOrderId());
        $result = $this->processTransaction->execute($tokenModel->getOrderId(), $order->getMollieTransactionId());
        $redirectToSuccessPage = $result->shouldRedirectToSuccessPage();

        $cart = null;
        if ($tokenModel->getCartId()) {
            $cart = $this->getCart(!$redirectToSuccessPage, $tokenModel->getCartId());
        }

        return [
            'paymentStatus' => strtoupper($result->getStatus()),
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
