<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\GraphQL\Plugin\PlaceOrder;

use Magento\QuoteGraphQl\Model\Resolver\PlaceOrder;
use Mollie\Payment\Service\Order\Transaction;

class SetTheRedirectUrl
{
    /**
     * @var Transaction
     */
    private $transaction;

    public function __construct(
        Transaction $transaction
    ) {
        $this->transaction = $transaction;
    }

    public function beforeResolve(
        PlaceOrder $subject,
        $field,
        $context,
        $info,
        ?array $value = null,
        ?array $args = null
    ) {
        if (isset($args['input']['mollie_return_url'])) {
            $this->transaction->setRedirectUrl($args['input']['mollie_return_url']);
        }
    }
}
