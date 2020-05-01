<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Mollie;

use Mollie\Payment\Config;

class PaymentMethods
{
    /**
     * @var Config
     */
    private $config;

    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * @var array
     */
    private $methods = [
        'mollie_methods_bancontact',
        'mollie_methods_banktransfer',
        'mollie_methods_belfius',
        'mollie_methods_creditcard',
        'mollie_methods_directdebit',
        'mollie_methods_ideal',
        'mollie_methods_kbc',
        'mollie_methods_paypal',
        'mollie_methods_paysafecard',
        'mollie_methods_sofort',
        'mollie_methods_inghomepay',
        'mollie_methods_giropay',
        'mollie_methods_eps',
        'mollie_methods_klarnapaylater',
        'mollie_methods_klarnasliceit',
        'mollie_methods_giftcard',
        'mollie_methods_przelewy24',
        'mollie_methods_applepay',
        'mollie_methods_mybank',
    ];

    public function getCodes()
    {
        return $this->methods;
    }

    public function getCodeswithTitle()
    {
        return array_map(function ($method) {
            return [
                'value' => $method,
                'label' => $this->config->getMethodTitle($method),
            ];
        }, $this->getCodes());
    }
}