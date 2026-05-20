<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\SelfTests;

use Exception;
use Hyva\Theme\Model\HyvaModulesConfig;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Module\Manager;

class IsHyvaThemesJsonCorrect extends AbstractSelfTest
{
    private const MODULE_METADATA = [
        'Mollie_HyvaCompatibility' => [
            'name' => 'Mollie Hyvä Compatibility',
            'url' => 'https://github.com/mollie/magento2-hyva-compatibility',
        ],
        'Mollie_HyvaCheckout' => [
            'name' => 'Mollie Hyvä Checkout',
            'url' => 'https://github.com/mollie/magento2-hyva-checkout',
        ],
    ];

    public function __construct(
        private Manager $moduleManager,
        private File $file
    ) {}

    public function execute(): void
    {
        if (
            !$this->moduleManager->isEnabled('Hyva_Theme') ||
            !class_exists(HyvaModulesConfig::class)
        ) {
            return;
        }

        $file = HyvaModulesConfig::FILE;
        $path = HyvaModulesConfig::PATH;

        $path = trim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        // @phpstan-ignore-next-line
        $fullPath = BP . DIRECTORY_SEPARATOR . $path . strtolower($file);

        $contents = $this->file->read($fullPath);

        if ($contents === false) {
            $this->addMessage('error', __('The Hyva Themes configuration file is missing. Please run the command `bin/magento hyva:modules:config:generate` to generate the file.'));

            return;
        }

        try {
            $json = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
            $this->validateThatModuleIsPresent($json, 'Mollie_HyvaCompatibility');
            $this->validateThatModuleIsPresent($json, 'Mollie_HyvaCheckout');
        } catch (Exception $exception) {
            $this->addMessage('error', __('The Hyva Themes configuration file is not a valid JSON file. Please run the command `bin/magento hyva:modules:config:generate` to generate the file.'));
        }
    }

    private function validateThatModuleIsPresent(array $json, string $module): void
    {
        if (!$this->moduleManager->isEnabled($module)) {
            return;
        }

        foreach ($json['extensions'] as $extension) {
            if (strpos($extension['src'], $module) !== false) {
                return;
            }
        }

        $metadata = self::MODULE_METADATA[$module];
        $link = sprintf('<a href="%s" target="_blank">%s</a>', $metadata['url'], $metadata['name']);

        $this->addMessage('error', __(
            'The %1 module is not present in the Hyva Themes configuration file. Please run the command `bin/magento hyva:modules:config:generate` to generate the file.',
            $link,
        ));
    }
}
