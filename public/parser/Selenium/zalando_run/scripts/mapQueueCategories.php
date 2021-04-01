<?php
exit;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use App\Connect;
use App\ProjectConfig;

include('/var/www/html/parser/Selenium/vendor/autoload.php');

$db = new Connect(ProjectConfig::getValue('stagingDbCredentials'));

$sql = "SELECT * FROM `queue` WHERE category LIKE '% %'";
$query = $db->query($sql);

while ($qQueue = $db->fetch($query)) {
    $sql = "SELECT id FROM categories WHERE title='{$qQueue['category']}'";
    $qry1 = $db->query($sql);
    if(!$qry1){
        continue;
    }
    if ($db->numRows($qry1) === 0) {
        continue;
    }

    $qCategory = $db->fetch($qry1);

    $sql = "UPDATE queue SET category='{$qCategory['id']}' WHERE id='{$qQueue['id']}'";
    $qry2 = $db->query($sql);
}
