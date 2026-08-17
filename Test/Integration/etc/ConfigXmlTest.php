<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\etc;

use Exception;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Module\Dir;
use Magento\Framework\Simplexml\Config;
use Magento\Framework\Simplexml\Element;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class ConfigXmlTest extends IntegrationTestCase
{
    private const CLASS_NAME_PATTERN = '/^[A-Z][A-Za-z0-9_]*(\\\\[A-Z][A-Za-z0-9_]*)+$/';

    public function testOnlyRefersToClassesThatExist(): void
    {
        $references = $this->getClassReferences($this->getConfigXml());

        $this->assertNotSame([], $references);

        foreach ($references as $path => $class) {
            $this->assertTrue(
                class_exists($class) || interface_exists($class),
                sprintf('"%s" refers to "%s", but that class does not exist', $path, $class)
            );
        }
    }

    public function testNoPaymentMethodDeclaresInstantPurchaseSupport(): void
    {
        $methods = $this->getConfigXml()->descend('default/payment')->children();

        foreach ($methods as $code => $method) {
            $this->assertFalse(
                isset($method->instant_purchase),
                sprintf(
                    '%s declares Magento Instant Purchase support, but that only works with a vault ' .
                    'payment method and this module has none',
                    $code
                )
            );
        }
    }

    private function getClassReferences(Element $node, string $path = ''): array
    {
        $references = $this->getClassReferencesFromAttributes($node, $path);

        if ($node->count() === 0) {
            return array_merge($references, $this->getClassReferenceFromValue($node, $path));
        }

        foreach ($node->children() as $name => $child) {
            $references = array_merge($references, $this->getClassReferences($child, $path . '/' . $name));
        }

        return $references;
    }

    private function getClassReferencesFromAttributes(Element $node, string $path): array
    {
        $references = [];

        foreach ($node->attributes() as $name => $attribute) {
            $value = (string)$attribute;

            if ($this->isClassName($value)) {
                $references[$path . '@' . $name] = $value;
            }
        }

        return $references;
    }

    private function getClassReferenceFromValue(Element $node, string $path): array
    {
        $value = trim((string)$node);

        if (!$this->isClassName($value)) {
            return [];
        }

        return [$path => $value];
    }

    private function isClassName(string $value): bool
    {
        return preg_match(self::CLASS_NAME_PATTERN, $value) === 1;
    }

    private function getConfigXml(): Element
    {
        $file = $this->objectManager->get(File::class);
        $moduleDir = $this->objectManager->get(Dir::class);

        $configXmlPath = $moduleDir->getDir('Mollie_Payment', Dir::MODULE_ETC_DIR) . '/config.xml';

        if (!$file->isFile($configXmlPath)) {
            throw new Exception('Config XML file does not exist: ' . $configXmlPath);
        }

        $xmlReader = $this->objectManager->get(Config::class);
        $xmlReader->loadFile($configXmlPath);

        return $xmlReader->getNode();
    }
}
