<?php

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

//This script just runs the parsing (adding) new product or category of products

use App\Connect;
use App\ProjectConfig;
use App\Services\Exception\QueueException;
use App\Services\Queue\Queue;
use App\Services\Task\Task;
use App\Zalando\ProductLinkParser;
use App\Zalando\ProductParser;
use Monolog\Logger;

include('/var/www/html/parser/Selenium/vendor/autoload.php');

$db = new Connect(ProjectConfig::getValue('stagingDbCredentials'));
$log = new Logger('productParser');

$queue = new Queue($db, $log);

$task = $queue->getNewTask('zalando');

if ($task === null) {
    echo "There's already running task in the queue or no new tasks to run";
    exit;
}

switch ($task->getType()) {
    case Task::CATEGORY:
        $task->setStatus(Task::PENDING);
        $productLinkParser = new ProductLinkParser($db, $log, $task);
        $productLinkLists = $productLinkParser->parse();
        if (count($productLinkLists) === 0) {
            $task->setStatus(Task::FAILED);
            exit;
        }

        foreach ($productLinkLists as $productList) {
            foreach ($productList as $productLink) {
                $category = $task->getCategory();
                $newTask = new Task($db, $productLink, $category, 'zalando', 'product', 'new');
                try {
                    $queue->add($newTask);
                } catch (QueueException $e) {
                    continue;
                }
            }
        }
        $task->setStatus(Task::SUCCESS);

        break;
    case Task::PRODUCT:
        $task->setStatus(Task::PENDING);
        $productParser = new ProductParser($db, $log, $task);

        $taskStatus = $productParser->parse($task->getCategory());

        try {
            $task->setStatus($taskStatus);
        } catch (\Throwable $e) {
            $log->critical('Unknown error: ' . $e->getMessage() . ' | Product url:' . $task->getLink());
            $task->setStatus(Task::FAILED);
        }

        break;
    default:
}
