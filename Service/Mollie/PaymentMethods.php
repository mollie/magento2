<?php
/*
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

    public const METHODS = [
        'mollie_methods_applepay',
        'mollie_methods_alma',
        'mollie_methods_bancomatpay',
        'mollie_methods_bancontact',
        'mollie_methods_banktransfer',
        'mollie_methods_belfius',
        'mollie_methods_billie',
        'mollie_methods_bizum',
        'mollie_methods_blik',
        'mollie_methods_creditcard',
        'mollie_methods_directdebit',
        'mollie_methods_eps',
        'mollie_methods_giftcard',
        'mollie_methods_googlepay',
        'mollie_methods_ideal',
        'mollie_methods_in3',
        'mollie_methods_kbc',
        'mollie_methods_klarna',
        'mollie_methods_klarnapaylater',
        'mollie_methods_klarnapaynow',
        'mollie_methods_klarnasliceit',
        'mollie_methods_mbway',
        'mollie_methods_mobilepay',
        'mollie_methods_multibanco',
        'mollie_methods_mybank',
        'mollie_methods_paybybank',
        'mollie_methods_paypal',
        'mollie_methods_paysafecard',
        'mollie_methods_pointofsale',
        'mollie_methods_payconiq',
        'mollie_methods_przelewy24',
        'mollie_methods_riverty',
        'mollie_methods_satispay',
        'mollie_methods_sofort',
        'mollie_methods_swish',
        'mollie_methods_trustly',
        'mollie_methods_twint',
        'mollie_methods_vipps',
        'mollie_methods_voucher',
    ];

    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    public function getCodes(): array
    {
        return static::METHODS;
    }

    public function getCodesWithTitle(): array
    {
        return array_map(function ($method) {
            return [
                'value' => $method,
                'label' => $this->config->getMethodTitle($method),
            ];
        }, $this->getCodes());
    }
}
