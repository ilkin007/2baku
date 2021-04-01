<?php
declare(strict_types=1);

namespace App\Zalando\Domain;

class GenericInteger
{
    private int $integer;

    public function __construct(int $integer)
    {
        $this->integer = filter_var($integer, FILTER_VALIDATE_INT);

        if ($this->integer === false) {
            throw new \InvalidArgumentException(sprintf('Integer \'%s\' is not a valid number', $integer));
        }
    }

    public function toInteger(): int
    {
        return $this->integer;
    }
}
