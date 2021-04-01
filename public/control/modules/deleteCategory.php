<?php

use App\Connect;
use App\ProjectConfig;
use Monolog\Logger;

if (!isset($_POST['id'])) {
    throw new Exception('Please provide an id');
}

$id = $_POST['id'];

$db = new Connect(ProjectConfig::getValue('stagingDbCredentials'));
$log = new Logger('margin');

$sql = "DELETE FROM `categories` WHERE `id`='{$id}'";

$query = $db->query($sql);

if (!$query) {
    throw new Exception("Couldn't delete category! Contact the developer!");
}

echo "<script>location.href='/category.php';</script>";
