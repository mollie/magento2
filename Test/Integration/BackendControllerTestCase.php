<?php

if (version_compare(\PHPUnit\Runner\Version::id(), '9.0', '>=')) {
    require __DIR__ . '/PHPUnit/BackendControllerTestCaseVersion9AndHigher.php';
} else {
    require __DIR__ . '/PHPUnit/BackendControllerTestCaseVersion8AndLower.php';
}
