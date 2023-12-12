<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Layer;

use Cowegis\Bundle\Contao\Exception\InvalidIconType;
use IteratorAggregate;

use function array_values;
use function sprintf;

/** @implements IteratorAggregate<int, LayerType> */
final class LayerTypeRegistry implements IteratorAggregate
{
    /** @var array<string, LayerType> */
    private array $layerTypes = [];

    /** @param LayerType[] $layerTypes */
    public function __construct(iterable $layerTypes = [])
    {
        foreach ($layerTypes as $layerType) {
            $this->register($layerType);
        }
    }

    public function register(LayerType $layerType): void
    {
        if (isset($this->layerTypes[$layerType->name()])) {
            throw new InvalidIconType(sprintf('Layer type named "%s" already registered', $layerType->name()));
        }

        if ($layerType instanceof LayerTypeRegistryAware) {
            $layerType->setRegistry($this);
        }

        $this->layerTypes[$layerType->name()] = $layerType;
    }

    public function has(string $layerType): bool
    {
        return isset($this->layerTypes[$layerType]);
    }

    public function get(string $layerType): LayerType
    {
        if (! isset($this->layerTypes[$layerType])) {
            throw new InvalidIconType(sprintf('Unknown layer type "%s"', $layerType));
        }

        return $this->layerTypes[$layerType];
    }

    public function getIterator(): LayerTypeIterator
    {
        return new LayerTypeIterator(...array_values($this->layerTypes));
    }
}
