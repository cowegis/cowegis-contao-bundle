<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Icon;

use Cowegis\Bundle\Contao\Exception\InvalidIconType;
use IteratorAggregate;
use function array_values;
use function sprintf;

final class IconTypeRegistry implements IteratorAggregate
{
    /** @var array<string, IconType> */
    private $iconTypes = [];

    /** @param IconType[] $iconTypes */
    public function __construct(iterable $iconTypes = [])
    {
        foreach ($iconTypes as $iconType) {
            $this->register($iconType);
        }
    }

    public function register(IconType $iconType) : void
    {
        if (isset($this->iconTypes[$iconType->name()])) {
            throw new InvalidIconType(sprintf('Icon type named "%s" already registered', $iconType->name()));
        }

        $this->iconTypes[$iconType->name()] = $iconType;
    }

    public function has(string $iconType) : bool
    {
        return isset($this->iconTypes[$iconType]);
    }

    public function get(string $iconType) : IconType
    {
        if (!isset($this->iconTypes[$iconType])) {
            throw new InvalidIconType(sprintf('Unknown Icon type "%s"', $iconType));
        }

        return $this->iconTypes[$iconType];
    }

    public function getIterator() : IconTypeIterator
    {
        return new IconTypeIterator(... array_values($this->iconTypes));
    }
}
