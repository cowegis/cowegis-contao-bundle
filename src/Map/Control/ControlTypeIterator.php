<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Control;

use Countable;
use Iterator;

use function count;

final class ControlTypeIterator implements Countable, Iterator
{
    /** @var ControlType[] */
    private $controlTypes;

    /** @var int */
    private $position;

    public function __construct(ControlType ...$controlTypes)
    {
        $this->controlTypes = $controlTypes;
        $this->position     = 0;
    }

    public function current(): ControlType
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
