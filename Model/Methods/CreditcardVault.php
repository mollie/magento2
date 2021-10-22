<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Methods;

use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Vault\Model\Method\Vault;
use Mollie\Payment\Model\Mollie;

/**
 * Class Creditcard
 *
 * @package Mollie\Payment\Model\Methods
 */
class CreditcardVault extends Vault
{
    /**
     * Payment method code
     *
     * @var string
     */
    const CODE = 'mollie_methods_creditcard_vault';

    public function order(InfoInterface $payment, $amount)
    {
        return $this;
    }

    public function authorize(InfoInterface $payment, $amount)
    {
        // Make sure the transaction is marked as pending so we don't get the wrong order state.
        $payment->setIsTransactionPending(true);

        /** @var OrderInterface $order */
        $order = $payment->getOrder();

        // Don't send the email just yet.
        $order->setCanSendNewEmailFlag(false);

        return parent::authorize($payment, $amount);
    }
}
