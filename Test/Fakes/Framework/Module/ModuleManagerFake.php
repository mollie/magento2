<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Fakes\Framework\Module;

use Magento\Framework\Module\Manager;

class ModuleManagerFake extends Manager
{
    /**
     * @var string[]
     */
    private array $disabledModules = [];

    public function givenModuleIsDisabled(string $moduleName): void
    {
        $this->disabledModules[] = $moduleName;
    }

    public function isEnabled($moduleName)
    {
        if (in_array($moduleName, $this->disabledModules, true)) {
            return false;
        }

        return parent::isEnabled($moduleName);
    }
}
