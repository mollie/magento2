<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 *  * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Plugin\Framework\View\Asset;

use Magento\Framework\View\Asset\Minification;

class DisableMinificationForComponentsJs
{
    public function afterGetExcludes(Minification $subject, $result, $contentType)
    {
        if ($contentType == 'js') {
            $result[] = 'js.mollie.com';
        }

        return $result;
    }
}