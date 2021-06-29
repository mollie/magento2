<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\GraphQL\Resolver\Cart;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;

class ResetCart implements ResolverInterface
{
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    public function __construct(
        CartRepositoryInterface $cartRepository
    ) {
        $this->cartRepository = $cartRepository;
    }

    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (empty($args['input']['cart_id'])) {
            throw new GraphQlInputException(__('Required parameter "cart_id" is missing'));
        }

        $maskedCartId = $args['input']['cart_id'];

        $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();

        /**
         * Use the ObjectManager to prevent setup:di:compile errors when people replace the GraphQL modules.
         */
        /** @var GetCartForUser $getCartForUser */
        $getCartForUser = ObjectManager::getInstance()->get(GetCartForUser::class);
        $cart = $getCartForUser->execute($maskedCartId, $context->getUserId(), $storeId);

        $cart->setIsActive(1);
        $this->cartRepository->save($cart);

        return [
            'cart' => [
                'model' => $cart,
            ],
        ];
    }
}
