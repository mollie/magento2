<?php

if (version_compare(\PHPUnit\Runner\Version::id(), '9.0', '>=')) {
    require __DIR__ . '/PHPUnit/ControllerTestCaseVersion9AndHigher.php';
} else {
    require __DIR__ . '/PHPUnit/ControllerTestCaseVersion8AndLower.php';
}
