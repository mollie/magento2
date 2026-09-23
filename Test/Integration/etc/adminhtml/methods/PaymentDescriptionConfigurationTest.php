<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\etc\adminhtml\methods;

use Mollie\Payment\Model\Adminhtml\Comment\AvailableDescriptionVariables;

class PaymentDescriptionConfigurationTest extends AbstractXmlConfiguration
{
    private const FIELD_ID = 'payment_description';

    public function testEveryMethodExposesThePaymentDescriptionField(): void
    {
        $methodsWithoutField = array_keys(array_filter(
            $this->getMethodXmlFiles(),
            fn ($methodXml): bool => !$this->hasField($methodXml, self::FIELD_ID),
        ));

        $this->assertSame(
            [],
            $methodsWithoutField,
            'These methods are missing the payment_description field: ' . implode(', ', $methodsWithoutField),
        );
    }

    public function testPaymentDescriptionFieldUsesTheMethodConfigPath(): void
    {
        foreach ($this->getMethodXmlFiles() as $method => $methodXml) {
            if (!$this->hasField($methodXml, self::FIELD_ID)) {
                continue;
            }

            $this->assertSame(
                sprintf('payment/mollie_methods_%s/payment_description', $method),
                (string) $this->getField($methodXml, self::FIELD_ID)->config_path,
                sprintf('Method "%s" has a payment_description field with the wrong config_path', $method),
            );
        }
    }

    public function testPaymentDescriptionFieldListsTheAvailableVariables(): void
    {
        foreach ($this->getMethodXmlFiles() as $method => $methodXml) {
            if (!$this->hasField($methodXml, self::FIELD_ID)) {
                continue;
            }

            $this->assertSame(
                AvailableDescriptionVariables::class,
                (string) $this->getField($methodXml, self::FIELD_ID)->comment->attributes()->model,
                sprintf('Method "%s" does not show the available description variables', $method),
            );
        }
    }
}
