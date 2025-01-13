<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Etc\Config;

use Magento\Config\Model\Config\Structure;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class MethodsConfigurationTest extends IntegrationTestCase
{
    public function methods(): array
    {
        return [
            ['mollie_methods_applepay'],
            ['mollie_methods_alma'],
            ['mollie_methods_bancomatpay'],
            ['mollie_methods_bancontact'],
            ['mollie_methods_banktransfer'],
            ['mollie_methods_belfius'],
            ['mollie_methods_billie'],
            ['mollie_methods_blik'],
            ['mollie_methods_creditcard'],
            ['mollie_methods_directdebit'],
            ['mollie_methods_eps'],
            ['mollie_methods_giftcard'],
            ['mollie_methods_googlepay'],
            ['mollie_methods_ideal'],
            ['mollie_methods_in3'],
            ['mollie_methods_kbc'],
            ['mollie_methods_klarna'],
            ['mollie_methods_klarnapaylater'],
            ['mollie_methods_klarnapaynow'],
            ['mollie_methods_klarnasliceit'],
            ['mollie_methods_voucher'],
            ['mollie_methods_multibanco'],
            ['mollie_methods_mybank'],
            ['mollie_methods_paypal'],
            ['mollie_methods_paysafecard'],
            ['mollie_methods_pointofsale'],
            ['mollie_methods_payconiq'],
            ['mollie_methods_przelewy24'],
            ['mollie_methods_riverty'],
            ['mollie_methods_satispay'],
            ['mollie_methods_sofort'],
            ['mollie_methods_trustly'],
            ['mollie_methods_twint'],
        ];
    }

    /**
     * @dataProvider methods
     * @magentoAppArea adminhtml
     */
    public function testHasTheCorrectValidationForExpireDays($method)
    {
        /** @var Structure $config */
        $config = $this->objectManager->get(Structure::class);

        $value = $config->getElementByConfigPath('payment/' . $method . '/days_before_expire');

        $this->assertStringContainsString('digits-range-1-365', $value->getAttribute('frontend_class'));
    }
}
