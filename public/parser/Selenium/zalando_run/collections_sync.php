<?php
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

//This script syncs categories in our db with collections on Shopify

use App\Connect;
use App\ProjectConfig;
use App\Zalando\CategoryCollectionSynchronizer;
use Monolog\Logger;

include('/var/www/html/parser/Selenium/vendor/autoload.php');

$db = new Connect(ProjectConfig::getValue('stagingDbCredentials'));
$log = new Logger('productSynchronizer');

$sync = new CategoryCollectionSynchronizer(
    ProjectConfig::getValue('shopifyAuthConfig'),
    $db,
    $log,
    'zalando'
);

$sync->initialize();
