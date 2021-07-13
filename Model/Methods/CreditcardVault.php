<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Methods;

use Magento\Payment\Model\InfoInterface;
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
}
