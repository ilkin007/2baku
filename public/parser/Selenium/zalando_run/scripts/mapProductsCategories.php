<?php
exit;
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use App\Connect;
use App\ProjectConfig;

include('/var/www/html/parser/Selenium/vendor/autoload.php');

$db = new Connect(ProjectConfig::getValue('stagingDbCredentials'));


$sql = "SELECT * FROM categories";
$query = $db->query($sql);

$categories = [];
while ($q = $db->fetch($query)) {
    $id = $q['id'];
    $title = $q['title'];

    $sql = "UPDATE products SET category='{$id}' WHERE category='{$title}'";
    $qry2 = $db->query($sql);
}
