<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\etc\adminhtml\methods;

class ManualCaptureConfigurationTest extends AbstractXmlConfigurationTest
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
}
