<?php
/**
 * Copyright © 2016 Magmodules. All rights reserved.
 * See COPYING.txt for license details.
 */

\Magento\Framework\Component\ComponentRegistrar::register(
    \Magento\Framework\Component\ComponentRegistrar::MODULE,
    'Magmodules_Mollie',
    __DIR__
);

// Require Mollie API
$vendorDir = require (__DIR__ . '/Api/Autoloader.php');