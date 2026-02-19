<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\etc\adminhtml\methods;

use Magento\Framework\Simplexml\Element;

class MethodConfigurationTest extends AbstractXmlConfiguration
{
    public function testHasCaptureModeAvailable(): void
    {
        $xmlFiles = $this->getMethodXmlFiles();

        foreach ($xmlFiles as $method => $file) {
            if (!$this->hasField($file, 'capture_mode')) {
                $this->fail(sprintf('Method "%s" does not have the capture_mode field', $method));
            }

            $this->addToAssertionCount(1);
        }
    }

    public function testHasValidConfigPaths(): void
    {
        $xmlFiles = $this->getMethodXmlFiles();

        foreach ($xmlFiles as $method => $file) {
            $this->validateMethod($file, $method);
        }
    }

    private function validateMethod(Element $file, string $method): void
    {
        foreach ($file->group->field as $field) {
            $id = $field->attributes()->id->__toString();

            $expectedPath = 'payment/mollie_methods_' . $method . '/' . $id;
            $actualPath = $field->config_path->__toString();
            if ($actualPath === '') {
                continue;
            }

            $this->assertEquals(
                $expectedPath,
                $actualPath,
                sprintf(
                    'We are expecting the config path to be: %s but got: %s',
                    $expectedPath,
                    $actualPath
                )
            );
        }
    }
}
