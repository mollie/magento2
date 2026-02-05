<?php

namespace Mollie\Payment\Test\Integration\etc\adminhtml\methods;

use Exception;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Module\Dir;
use Magento\Framework\Simplexml\Config;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class MethodConfigurationTest extends IntegrationTestCase
{
    public function getXmlFiles(): array
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

    public function testHasValidConfigPaths(): void
    {
        $xmlFiles = $this->getXmlFiles();

        foreach ($xmlFiles as $method => $file) {
            $this->validateMethod($file, $method);
        }
    }

    private function validateMethod(mixed $file, int|string $method): void
    {
        foreach ($file->descend('group')->getChildren()->field as $field) {
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
