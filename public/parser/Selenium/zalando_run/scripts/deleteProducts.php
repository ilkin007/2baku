<?php
exit;
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

use App\Connect;
use App\ProjectConfig;
use App\Zalando\CreateSynchronizer;
use Monolog\Logger;

include('/var/www/html/parser/Selenium/vendor/autoload.php');

$shopifyAuthConfig = [
    'ShopUrl'  => '2baku-de.myshopify.com/',
    'ApiKey'   => '47eaa26ce29c932c6a94b848fb0febe5',
    'Password' => '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'
];

$db = new Connect(ProjectConfig::getValue('stagingDbCredentials'));
$log = new Logger('productSynchronizer');

$productSynchronizer = new CreateSynchronizer($shopifyAuthConfig, $db, $log, 'zalando');


$productSynchronizer->deleteAllProducts();
