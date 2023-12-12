<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Style;

use Countable;
use Iterator;

use function count;

/** @implements Iterator<array-key,StyleType> */
final class StyleTypeIterator implements Countable, Iterator
{
    /** @var StyleType[] */
    private array $controlTypes;

    private int $position;

    public function __construct(StyleType ...$controlTypes)
    {
        $this->controlTypes = $controlTypes;
        $this->position     = 0;
    }

    public function current(): StyleType
    {
        return $this->controlTypes[$this->position];
    }

    public function next(): void
    {
        $this->position++;
    }

    public function key(): int
    {
        return $this->position;
    }

    public function valid(): bool
    {
        return $this->position < count($this->controlTypes);
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function count(): int
    {
        return count($this->controlTypes);
    }
}
