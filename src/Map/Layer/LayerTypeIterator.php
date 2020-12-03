<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Layer;

use Countable;
use Iterator;

use function count;

final class LayerTypeIterator implements Countable, Iterator
{
    /** @var LayerType[] */
    private $layerTypes;

    /** @var int */
    private $position;

    public function __construct(LayerType ...$layerTypes)
    {
        $this->layerTypes = $layerTypes;
        $this->position   = 0;
    }

    public function current(): LayerType
    {
        return $this->layerTypes[$this->position];
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
        return $this->position < count($this->layerTypes);
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function count(): int
    {
        return count($this->layerTypes);
    }
}
