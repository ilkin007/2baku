<?php
exit;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use App\Connect;
use App\ProjectConfig;

include('/var/www/html/parser/Selenium/vendor/autoload.php');

$db = new Connect(ProjectConfig::getValue('stagingDbCredentials'));

$sql = "SELECT `sizes` FROM `products`";
$query = $db->query($sql);

$distinctSizes = [];
while ($q = $db->fetch($query)) {
    $size = json_decode($q['sizes'], true);
    foreach ($size as $key => $value) {
        if (!array_key_exists($key, $distinctSizes)) {
            $distinctSizes[] = $key;
        }
    }
}

$distinctSizes = array_unique($distinctSizes);


foreach ($distinctSizes as $size) {
    echo '"' . $size . '",' . PHP_EOL;
}
