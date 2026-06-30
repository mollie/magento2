<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\etc\adminhtml\methods;

class ManualCaptureConfigurationTest extends AbstractXmlConfiguration
{
    public function testManualCaptureMethodHaveCaptureWhenOptionAvailable(): void
    {
        $configXml = $this->getGeneralXmlConfigFile();
        $methodXmlFiles = $this->getMethodXmlFiles();

        foreach ($configXml->default->payment->children() as $groupName => $config) {
            if ($groupName === 'mollie_methods_general') {
                continue;
            }

            if (!isset($config->can_change_capture_method)) {
                continue;
            }

            [, $method] = explode('mollie_methods_', $groupName);
            $methodXml = $methodXmlFiles[$method];

            $this->assertTrue(
                $this->hasField($methodXml, 'capture_mode'),
                sprintf('Method "%s" does not have the capture_mode field', $method)
            );

            $this->assertTrue(
                $this->hasField($methodXml, 'when_to_capture'),
                sprintf('Method "%s" does not have the when_to_capture field', $method)
            );
        }
    }

    public function testGooglePaySupportsManualCaptureConfiguration(): void
    {
        $configXml = $this->getGeneralXmlConfigFile();
        $googlePayConfig = $configXml->default->payment->mollie_methods_googlepay;

        $this->assertSame(
            '1',
            (string) $googlePayConfig->can_change_capture_method,
            'Google Pay should allow changing the capture method'
        );

        $googlePayXml = $this->getMethodXmlFiles()['googlepay'];

        $this->assertTrue(
            $this->hasField($googlePayXml, 'capture_mode'),
            'Google Pay does not have the capture_mode field'
        );

        $this->assertTrue(
            $this->hasField($googlePayXml, 'when_to_capture'),
            'Google Pay does not have the when_to_capture field'
        );
    }
}
