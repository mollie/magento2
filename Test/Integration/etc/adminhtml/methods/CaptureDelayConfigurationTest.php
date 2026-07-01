<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\etc\adminhtml\methods;

class CaptureDelayConfigurationTest extends AbstractXmlConfiguration
{
    private const CARD_BASED_METHODS = ['creditcard', 'applepay', 'googlepay'];

    public function testCaptureDelayIsOnlyAvailableForCardBasedMethods(): void
    {
        $methodXmlFiles = $this->getMethodXmlFiles();

        foreach ($methodXmlFiles as $method => $methodXml) {
            $hasCaptureDelay = $this->hasField($methodXml, 'capture_delay');

            if (in_array($method, self::CARD_BASED_METHODS, true)) {
                $this->assertTrue(
                    $hasCaptureDelay,
                    sprintf('Card based method "%s" should have the capture_delay field', $method)
                );

                continue;
            }

            $this->assertFalse(
                $hasCaptureDelay,
                sprintf('Method "%s" should not have the capture_delay field as it is not card based', $method)
            );
        }
    }

    public function testCaptureDelayUnitFollowsCaptureDelay(): void
    {
        $methodXmlFiles = $this->getMethodXmlFiles();

        foreach ($methodXmlFiles as $method => $methodXml) {
            $this->assertSame(
                $this->hasField($methodXml, 'capture_delay'),
                $this->hasField($methodXml, 'capture_delay_unit'),
                sprintf('Method "%s" must expose capture_delay and capture_delay_unit together', $method)
            );
        }
    }
}
