<?php

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

//In this script we get random already loaded to Shopify, product, from the staging database.
//Then we try to reparse it from Zalando, if it is still there, we update the data in staging database and Shopify.
//In case it's already not exist in Zalando, we just set it as out of stock on Shopify side.

use App\Connect;
use App\ProjectConfig;
use App\Zalando\ProductUpdater;
use Monolog\Logger;

include('/var/www/html/parser/Selenium/vendor/autoload.php');

$db = new Connect(ProjectConfig::getValue('stagingDbCredentials'));
$log = new Logger('productUpdate');

$productUpdater = new ProductUpdater($db, $log);

$productId = null;

if (isset($argv1[1])) {
    $productId = $argv1[1];
}

$productUpdater->updateRandomProduct();
