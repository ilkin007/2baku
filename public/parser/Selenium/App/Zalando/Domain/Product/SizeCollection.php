<?php
declare(strict_types=1);

namespace App\Zalando\Domain\Product;

class SizeCollection implements \Countable, \Iterator, \ArrayAccess
{
    private array $array = [];
    private int $position = 0;

    public function __construct(array $array = [])
    {
        foreach ($array as $key => $value) {
            $this->offsetSet($key, $value);
        }
    }

    public function current()
    {
        return $this->array[$this->position];
    }

    public function next()
    {
        $this->position++;
    }

    public function key()
    {
        return $this->position;
    }

    public function valid()
    {
        return array_key_exists($this->position, $this->array);
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function offsetExists($offset)
    {
        return isset($this->array[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->array[$offset];
    }

    public function offsetSet($offset, $value)
    {
        if (empty($offset)) {
            $this->array[] = $value;
        } else {
            $this->array[$offset] = $value;
        }
    }

    public function offsetUnset($offset)
    {
        unset($this->array[$offset]);
    }

    public function count()
    {
        return count($this->array);
    }

    public function toJson(): string
    {
        return json_encode($this->array);
    }

    public function toArray(): array
    {
        return $this->array;
    }
}
