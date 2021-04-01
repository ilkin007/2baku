<?php
//This script sets all the category queue tasks back to the status new every midnight
//If the amount of products is less than the minimum, set in the configuration when adding this task
//We have a mechanism to detect duplicate products (by unique links to the product on zalando), so we will definitely avoid them

use App\Connect;
use App\ProjectConfig;
use App\Services\Queue\Queue;
use Monolog\Logger;

include('/var/www/html/parser/Selenium/vendor/autoload.php');

$db = new Connect(ProjectConfig::getValue('stagingDbCredentials'));
$log = new Logger('notUpdatedSetter');

$productUpdater = new Queue($db, $log);

$productUpdater->setCategoriesToGetNewProducts();
