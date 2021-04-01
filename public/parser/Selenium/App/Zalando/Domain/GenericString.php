<?php
declare(strict_types=1);

namespace App\Zalando\Domain;

class GenericString
{
    private string $string;

    public function __construct(string $string)
    {
        $this->string = $string;
    }

    public function toString(): string
    {
        return $this->string;
    }

    public function toDbString(): string
    {
        return str_replace("'", "", $this->string);
    }
}
