<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\etc\adminhtml\methods;

use Exception;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Module\Dir;
use Magento\Framework\Simplexml\Config;
use Magento\Framework\Simplexml\Element;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

abstract class AbstractXmlConfigurationTest extends IntegrationTestCase
{
    protected function getField(Element $file, string $name): Element
    {
        foreach ($file->group->field as $field) {
            $id = $field->attributes()->id->__toString();

            if ($id === $name) {
                return $field;
            }
        }

        throw new Exception(sprintf('Field "%s" not found', $name));
    }

    protected function getGeneralXmlConfigFile(): Element
    {
        $file = $this->objectManager->get(File::class);
        $xmlReader = $this->objectManager->get(Config::class);

        $moduleDir = $this->objectManager->get(Dir::class);

        $etcPath = $moduleDir->getDir('Mollie_Payment', Dir::MODULE_ETC_DIR);
        $configXmlPath = $etcPath . '/config.xml';

        if (!$file->isFile($configXmlPath)) {
            throw new Exception('Config XML file does not exist: ' . $configXmlPath);
        }

        $xmlReader->loadFile($configXmlPath);
        return $xmlReader->getNode();
    }

    protected function getMethodXmlFiles(): array
    {
        $file = $this->objectManager->get(File::class);
        $xmlReader = $this->objectManager->get(Config::class);

        $moduleDir = $this->objectManager->get(Dir::class);

        $etcPath = $moduleDir->getDir('Mollie_Payment', Dir::MODULE_ETC_DIR);
        $methodsPath = $etcPath . '/adminhtml/methods/';

        if (!$file->isDirectory($methodsPath)) {
            throw new Exception('Methods path does not exist: ' . $methodsPath);
        }

        $xmlFiles = [];
        $files = $file->readDirectory($methodsPath);
        foreach ($files as $path) {
            if ($file->isFile($path) && pathinfo($path, PATHINFO_EXTENSION) === 'xml') {
                $filename = basename($path, '.xml');
                $xmlReader->loadFile($path);
                $xmlFiles[$filename] = $xmlReader->getNode();
            }
        }

        return $xmlFiles;
    }

    protected function hasField(Element $file, string $name): bool
    {
        try {
            $this->getField($file, $name);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
