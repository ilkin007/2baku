<?php
//This script sets all the products as not updated every midnight
//To be sure that the products will be updated later on during the day

use App\Connect;
use App\ProjectConfig;
use App\Zalando\ProductUpdater;
use Monolog\Logger;

include('/var/www/html/parser/Selenium/vendor/autoload.php');

$db = new Connect(ProjectConfig::getValue('stagingDbCredentials'));
$log = new Logger('notUpdatedSetter');

$productUpdater = new ProductUpdater($db, $log);

$productUpdater->setAllNotUpdated();
