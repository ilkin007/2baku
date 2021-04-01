<?php
declare(strict_types=1);

namespace App\Zalando\Domain;

class GenericFloat
{
    const PRECISION_DEFAULT = 4;
    const SCALE_DEFAULT = 2;

    private float $float;

    public function __construct(float $float)
    {
        $this->float = filter_var($float, FILTER_VALIDATE_FLOAT);

        if ($this->float === false) {
            throw new \InvalidArgumentException(
                sprintf('Float \'%s\' is not a valid floating point number.', $float)
            );
        }
    }

    public function toFloat(): float
    {
        return $this->float;
    }
}
