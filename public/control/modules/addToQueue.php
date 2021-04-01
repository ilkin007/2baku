<?php

use App\Connect;
use App\ProjectConfig;
use App\Services\Queue\Queue;
use App\Services\Task\Task;
use Monolog\Logger;

if (!isset($_POST[ 'link' ])) {
    throw new Exception('Please provide a link');
}

$link = $_POST[ 'link' ];
$categoryId = $_POST[ 'category_id' ];
$type = $_POST[ 'type' ];
$minimum = $_POST[ 'minimum' ];
$limit = $_POST[ 'limit' ];

$db = new Connect(ProjectConfig::getValue('stagingDbCredentials'));
$log = new Logger('queue');

$sql = "SELECT `title` FROM `categories` WHERE `id`='{$categoryId}'";
$query = $db->query($sql);
if($db->numRows($query) === 0){
    echo "Error! The category with id {$categoryId} was not found";
}
$q = $db->fetch($query);
$categoryTitle = $q['title'];

$category = new \App\Zalando\Domain\Product\ProductCategory($categoryTitle, $categoryId);

$task = new Task($db, trim($link), $category, 'zalando', $type, 'new', $minimum, $limit);

$queue = new Queue($db, $log);

$newTaskId = $queue->add($task);

echo "<script>location.href='/parsing.php';</script>";
