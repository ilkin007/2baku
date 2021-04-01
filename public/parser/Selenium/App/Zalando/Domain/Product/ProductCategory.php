<?php
declare(strict_types=1);

namespace App\Zalando\Domain\Product;

use App\Zalando\Domain\GenericString;
use Ramsey\Uuid\Uuid;

class ProductCategory extends GenericString
{
    private string $id;

    //TODO Make it UUID string as everywhere and strict, since its ID
    //TODO Don't forget to update panel interface as well, to generate UUID there
    public function __construct(string $string, ?string $id = null)
    {
        parent::__construct($string);

        $id = $id ?? Uuid::uuid1()->toString();
        $this->id = $id;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }
}
