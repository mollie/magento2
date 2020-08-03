<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 *  * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Mollie\Compatibility;

interface CompatibilityTestInterface
{
    /**
     * @param array $results
     * @return array
     */
    public function execute(array $results);
}