<?php

namespace App\Services\Task;

use App\Connect;
use App\Services\Exception\TaskException;
use App\Zalando\Domain\Product\ProductCategory;
use Ramsey\Uuid\Uuid;

class Task
{
    const CATEGORY = 'category';
    const PRODUCT = 'product';

    const NEW = 'new';
    const PENDING = 'pending';
    const FAILED = 'failed';
    const SUCCESS = 'success';
    const DUPLICATE = 'duplicate';

    const AVAILABLE_TASK_TYPES = [
        self::CATEGORY,
        self::PRODUCT
    ];

    const AVAILABLE_TASK_STATUSES = [
        self::NEW,
        self::PENDING,
        self::FAILED,
        self::SUCCESS
    ];

    public function __construct(
        private Connect $db,
        private string $link,
        private ProductCategory $category,
        private string $website,
        private string $type,
        private string $status,
        private ?string $minimum = null,
        private ?string $limit = null,
        private ?string $id = null
    ) {
        $this->db = $db;

        $this->id = $id;
        if ($id === null) {
            $this->id = Uuid::uuid1()->toString();
        }

        $this->checkForType($type);
        $this->checkForStatus($status);
    }

    private function checkForType(string $type): void
    {
        if (!in_array($type, self::AVAILABLE_TASK_TYPES)) {
            throw new TaskException("Task type {$type} was not found");
        }
    }

    private function checkForStatus(string $status): void
    {
        if (!in_array($status, self::AVAILABLE_TASK_STATUSES)) {
            throw new TaskException("Task status {$status} was not found");
        }
    }

    public function getDb(): Connect
    {
        return $this->db;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function getLimit(): ?string
    {
        return $this->limit;
    }

    public function getMinimum(): ?string
    {
        return $this->minimum;
    }

    public function getCategory(): ProductCategory
    {
        return $this->category;
    }

    public function getWebsite(): string
    {
        return $this->website;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $sql = "UPDATE `queue` SET `status`='{$status}' WHERE id='{$this->getId()}'";
        $query = $this->db->query($sql);

        if (!$query) {
            throw new TaskException("Couldn't set {$status} status to the task! SQL error!");
        }

        $this->status = $status;
    }
}
