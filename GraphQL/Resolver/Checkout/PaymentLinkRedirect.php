<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\GraphQL\Resolver\Checkout;

use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Mollie\Payment\Service\Magento\PaymentLinkRedirect as PaymentLinkRedirectService;

class PaymentLinkRedirect implements ResolverInterface
{
    /**
     * @var PaymentLinkRedirectService
     */
    private $paymentLinkRedirect;

    public function __construct(
        PaymentLinkRedirectService $paymentLinkRedirect
    ) {
        $this->paymentLinkRedirect = $paymentLinkRedirect;
    }

    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        $order = $args['order'];

        try {
            $result = $this->paymentLinkRedirect->execute($order);
        } catch (NotFoundException $exception) {
            throw new GraphQlNoSuchEntityException(__('Order not found'));
        }

        return [
            'already_paid' => $result->isAlreadyPaid(),
            'redirect_url' => $result->getRedirectUrl(),
            'is_expired' => $result->isExpired(),
        ];
    }
}
