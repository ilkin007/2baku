<?php

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

//This script just enters Zalando to refresh cookies

use App\Connect;
use App\ProjectConfig;
use App\Zalando\ProductParser;
use Monolog\Logger;

include('/var/www/html/parser/Selenium/vendor/autoload.php');

$db = new Connect(ProjectConfig::getValue('stagingDbCredentials'));
$log = new Logger('productParser');

$productParser = new ProductParser($db, $log);

$productParser->prepareCookies();
