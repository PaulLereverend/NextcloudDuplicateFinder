<?php

if (!defined('PHPUNIT_RUN')) {
    define('PHPUNIT_RUN', 1);
}

require_once __DIR__.'/../../../lib/base.php';

// Fix for "Autoload path not allowed: .../tests/lib/testcase.php"
\OC::$loader->addValidRoot(OC::$SERVERROOT . '/tests');

// Fix for "Autoload path not allowed: .../duplicatefinder/tests/testcase.php"
\OC_App::loadApp('duplicatefinder');

if (!class_exists('PHPUnit\Framework\TestCase')) {
    require_once('PHPUnit/Autoload.php');
}

OC_Hook::clear();
