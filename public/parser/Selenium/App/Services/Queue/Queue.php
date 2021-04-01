<?php

namespace App\Services\Queue;

use App\Connect;
use App\Services\Exception\QueueException;
use App\Services\Task\Task;
use App\Zalando\AbstractSynchronizer;
use App\Zalando\Domain\Product\Product;
use App\Zalando\Domain\Product\ProductCategory;
use Monolog\Logger;

class Queue
{
    private $db;
    private $log;

    public function __construct(Connect $db, Logger $log)
    {
        $this->db = $db;
        $this->log = $log;
    }

    private function isProductExistsInTheQueue(string $link): bool
    {
        $sql = "SELECT * FROM `queue` WHERE `link`='{$link}'";

        $query = $this->db->query($sql);

        if (!$query) {
            throw new QueueException("Couldn't check whether product already exists in the queue");
        }

        if ($this->db->numRows($query) > 0) {
            return true;
        }

        return false;
    }

    public function add(Task $task): string
    {
        $id = $task->getId();
        $link = $task->getLink();
        $minimum = $task->getMinimum() ?? null;
        $limit = $task->getLimit() ?? null;
        $categoryId = $task->getCategory()->getId();

        if ($this->isProductExistsInTheQueue($link)) {
            throw new QueueException('Product already exists!');
        }

        $type = $task->getType();
        $website = $task->getWebsite();
        $status = $task->getStatus();

        $sql = "INSERT INTO `queue`
        (`id`, `link`, `limitation`, `minimum`, `category_id`, `type`, `website`, `status`)
        VALUES
        ('{$id}', '{$link}', '{$limit}', '{$minimum}', '{$categoryId}', '{$type}', '{$website}', '{$status}')";

        $query = $this->db->query($sql);

        if (!$query) {
            throw new QueueException("Couldn't add queue item");
        }

        return $id;
    }

    private function getCategoryTitleById(string $categoryId): string
    {
        $sql = "SELECT * FROM `categories` WHERE id='{$categoryId}'";
        $query = $this->db->query($sql);

        if ($this->db->numRows($query) === 0) {
            $this->log->critical('Category with id ' . $categoryId . ' does not exist!');
        }
        $q = $this->db->fetch($query);

        return $q['title'];
    }

    private function getCategoryIdByTitle(string $category): string
    {
        $sql = "SELECT * FROM `categories` WHERE title='{$category}'";
        $query = $this->db->query($sql);

        if ($this->db->numRows($query) === 0) {
            $this->log->critical('Category with title ' . $category . ' does not exist!');
        }
        $q = $this->db->fetch($query);

        return $q['id'];
    }

    public function getNewTask(string $website): ?Task
    {
        //Get the item with status new when there's no any pending task for now
        $sql = "SELECT * FROM `queue` WHERE `status` = 'new' AND `website`='{$website}' AND (SELECT COUNT(*) FROM `queue` WHERE `status` = 'pending') < 5 LIMIT 0,1";

        $query = $this->db->query($sql);

        if (!$query) {
            throw new QueueException("Can't get queue data from the database");
        }

        if ($this->db->numRows($query) === 0) {
            return null;
        }

        $q = $this->db->fetch($query);
        $categoryTitle = $this->getCategoryTitleById($q['category_id']);
        $category = new ProductCategory($categoryTitle, $q['category_id']);

        return new Task($this->db, $q['link'], $category, $website, $q['type'], $q['status'], $q['minimum'],
            $q['limitation'], $q['id']);
    }

    public function removeDuplicates(): void
    {
        $sql = "SELECT id FROM `queue` GROUP BY `link` HAVING COUNT(`link`) > 1";
        $query = $this->db->query($sql);

        $duplicatesCount = 0;

        if ($query) {
            $duplicatesCount = $this->db->numRows($query);
        } else {
            $this->log->error('Error while getting duplicate products to delete');
        }

        $ids = '';
        $q = $this->db->fetch($query);
        while ($q) {
            $ids .= "'" . $q['id'] . "',";
        }
        $ids = substr($ids, 0, -1);

        $sql = "DELETE FROM `queue` WHERE `id` IN ({$ids}) AND `status`='new'";

        $query = $this->db->query($sql);

        if ($query) {
            $this->log->info('Removing duplicate products: ' . $duplicatesCount . ' item(s)');
        } else {
            $this->log->error('Error while removing duplicate products, ' . $duplicatesCount . ' found');
        }
    }

    public function setCategoriesToGetNewProducts()
    {
        $categories = $this->getCategoriesToRefill();

        foreach ($categories as $categoryId) {
            $sql = "UPDATE `queue` SET `status`='" . Task::NEW . "' WHERE `id`='{$categoryId}'";

            $query = $this->db->query($sql);

            if (!$query) {
                $this->log->error('Error while setting the category to status=new to add some products there');
            }
        }
    }

    private function getCategoriesToRefill(): array
    {
        $sql = "SELECT `id`, `minimum`, `category_id` FROM `queue`
                WHERE `type`='category'
                AND `status` != '" . Task::NEW . "'";

        $query = $this->db->query($sql);

        if (!$query) {
            $this->log->error('Error while getting the list of category tasks from the queue');
        }

        $categoriesList = [];

        while ($q = $this->db->fetch($query)) {
            $id = $q['id'];
            $categoryId = $q['category_id'];
            $minimumOfProducts = $q['minimum'];

            $sql = "SELECT COUNT(*) cnt FROM `products` WHERE `category_id`='{$categoryId}'
                    AND `add_status`='" . AbstractSynchronizer::SUCCESS . "'
                    AND (`sizes` IS NULL OR `sizes` = '')";

            $getCountQuery = $this->db->query($sql);

            if (!$getCountQuery) {
                $this->log->error('Error while getting the amount of active products in the category');
            }

            $a = $this->db->fetch($getCountQuery);

            $cnt = $a['cnt'];

            if ($cnt < $minimumOfProducts) {
                $categoriesList[] = $id;
            }
        }

        return $categoriesList;
    }
}
