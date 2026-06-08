<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\GraphQL\Resolver\Cart;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;

class ResetCart implements ResolverInterface
{
    public function __construct(
        private MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId,
        private CartRepositoryInterface $cartRepository
    ) {}

    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        if (empty($args['input']['cart_id'])) {
            throw new GraphQlInputException(__('Required parameter "cart_id" is missing'));
        }

        $maskedCartId = $args['input']['cart_id'];

        $cartId = $this->maskedQuoteIdToQuoteId->execute($maskedCartId);
        $cart = $this->cartRepository->get($cartId);

        $this->validateCartOwnership($cart, $context->getUserId(), $maskedCartId);

        $cart->setIsActive(1);
        $this->cartRepository->save($cart);

        return [
            'cart' => [
                'model' => $cart,
            ],
        ];
    }

    private function validateCartOwnership(CartInterface $cart, int $customerId, string $cartHash): void
    {
        $cartCustomerId = (int) $cart->getCustomerId();
        /* Guest cart, allow operations */
        if (0 === $cartCustomerId && (null === $customerId || 0 === $customerId)) {
            return;
        }

        if ($cartCustomerId !== $customerId) {
            throw new GraphQlAuthorizationException(
                __(
                    'The current user cannot perform operations on cart "%masked_cart_id"',
                    ['masked_cart_id' => $cartHash],
                ),
            );
        }
    }
}
