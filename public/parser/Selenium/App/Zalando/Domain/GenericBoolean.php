<?php
declare(strict_types=1);

namespace App\Zalando\Domain;

class GenericBoolean
{
    const TRUE = 'true';
    const FALSE = 'false';

    const EXCEPTION_INVALID_ARGUMENT = 'Value should one of: %s';

    private bool $boolean;

    public function __construct(bool $boolean)
    {
        if (is_numeric($boolean)) {
            if (!in_array((int)$boolean, [1, 0], true)) {
                throw new \InvalidArgumentException(sprintf(self::EXCEPTION_INVALID_ARGUMENT, 1, 0));
            }

            $this->boolean = ((int)$boolean === 1);

            return;
        }

        if (is_string($boolean)) {
            $boolean = strtolower($boolean);

            if (!in_array($boolean, [self::TRUE, self::FALSE], true)) {
                throw new \InvalidArgumentException(sprintf(self::EXCEPTION_INVALID_ARGUMENT));
            }

            $this->boolean = ($boolean === self::TRUE);

            return;
        }

        $this->boolean = (bool)$boolean;
    }

    public function toBoolean(): bool
    {
        return $this->boolean;
    }

    public function toInteger(): int
    {
        return ($this->boolean === true) ? 1 : 0;
    }

    public function equals(GenericBoolean $boolean): bool
    {
        return $this->toBoolean() === $boolean->toBoolean();
    }
}
