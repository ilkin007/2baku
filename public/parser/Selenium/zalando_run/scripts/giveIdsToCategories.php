<?php
exit;
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use App\Connect;
use App\ProjectConfig;

include('/var/www/html/parser/Selenium/vendor/autoload.php');

$db = new Connect(ProjectConfig::getValue('stagingDbCredentials'));

$sql = 'SELECT * FROM categories';

$query = $db->query($sql);

while ($q = $db->fetch($query)) {
    $uuid = \Ramsey\Uuid\Uuid::uuid1();
    $sql = "UPDATE categories SET id='{$uuid}' WHERE sorted_id='{$q['sorted_id']}'";

    $qry = $db->query($sql);
}
