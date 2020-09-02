<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

if (version_compare(\PHPUnit\Runner\Version::id(), '9.0', '>=')) {
    require __DIR__ . '/PHPUnit/UnitTestCaseVersion9AndHigher.php';
} else {
    require __DIR__ . '/PHPUnit/UnitTestCaseVersion8AndLower.php';
}
