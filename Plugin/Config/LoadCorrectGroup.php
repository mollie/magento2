<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Plugin\Config;

use Magento\Config\Model\Config\Loader;

class LoadCorrectGroup
{
    public function beforeGetConfigByPath(Loader $subject, $path, $scope, $scopeId, $full = true)
    {
        $groups = ['mollie_second_chance_email', 'mollie_advanced', 'mollie_payment_methods', 'mollie_general'];
        if (in_array($path, $groups)) {
            $path = 'payment';
        }

        return [$path, $scope, $scopeId, $full];
    }
}