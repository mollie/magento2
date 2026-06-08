<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Config\Debug;

use Magento\Framework\Module\FullModuleList;
use Magento\Framework\Module\ModuleListInterface;

class ModulesCollector implements CollectorInterface
{
    public function __construct(
        private readonly FullModuleList $fullModuleList,
        private readonly ModuleListInterface $enabledModuleList,
    ) {
    }

    /**
     * Builds the modules.txt body listing every module with its enabled/disabled state.
     *
     * Output mirrors `bin/magento module:status`: an "enabled" section followed by a
     * "disabled" section, each alphabetically sorted. "None" is written when a section
     * is empty.
     */
    public function collect(): array
    {
        return ['modules.txt' => $this->render()];
    }

    public function getReadmeDescription(): string
    {
        return "- modules.txt\n"
            . "  The list of Magento modules installed on your store, separated into enabled\n"
            . "  and disabled (equivalent to the output of \"bin/magento module:status\").";
    }

    public function render(): string
    {
        $all = $this->fullModuleList->getNames();
        $enabled = $this->enabledModuleList->getNames();

        $enabledSorted = $enabled;
        sort($enabledSorted, SORT_STRING);

        $disabled = array_values(array_diff($all, $enabled));
        sort($disabled, SORT_STRING);

        $lines = ['List of enabled modules:'];
        $lines[] = $enabledSorted === [] ? 'None' : implode("\n", $enabledSorted);
        $lines[] = '';
        $lines[] = 'List of disabled modules:';
        $lines[] = $disabled === [] ? 'None' : implode("\n", $disabled);

        return implode("\n", $lines) . "\n";
    }
}
