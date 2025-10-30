<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\GraphQL\Plugin\PlaceOrder;

use Magento\QuoteGraphQl\Model\Resolver\PlaceOrder;
use Mollie\Payment\Service\Order\Transaction;

class SetTheRedirectUrl
{
    public function __construct(
        private Transaction $transaction
    ) {}

    public function beforeResolve(
        PlaceOrder $subject,
        $field,
        $context,
        $info,
        ?array $value = null,
        ?array $args = null,
    ): void {
        if (isset($args['input']['mollie_return_url'])) {
            $this->transaction->setRedirectUrl($args['input']['mollie_return_url']);
        }
    }
}
