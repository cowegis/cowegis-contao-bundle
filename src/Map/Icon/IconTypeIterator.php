<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Icon;

use Countable;
use Iterator;

use function count;

final class IconTypeIterator implements Countable, Iterator
{
    /** @var IconType[] */
    private array $iconTypes;

    private int $position;

    public function __construct(IconType ...$iconTypes)
    {
        $this->iconTypes = $iconTypes;
        $this->position  = 0;
    }

    public function current(): IconType
    {
        return $this->iconTypes[$this->position];
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
        return $this->position < count($this->iconTypes);
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function count(): int
    {
        return count($this->iconTypes);
    }
}
