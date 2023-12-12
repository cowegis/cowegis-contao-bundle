<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Control;

use Cowegis\Bundle\Contao\Exception\InvalidControlType;
use IteratorAggregate;

use function array_values;
use function sprintf;

/** @implements IteratorAggregate<int, ControlType> */
final class ControlTypeRegistry implements IteratorAggregate
{
    /** @var array<string, ControlType> */
    private array $controlTypes = [];

    /** @param ControlType[] $controlTypes */
    public function __construct(iterable $controlTypes)
    {
        foreach ($controlTypes as $controlType) {
            $this->register($controlType);
        }
    }

    public function register(ControlType $controlType): void
    {
        if (isset($this->controlTypes[$controlType->name()])) {
            throw new InvalidControlType(sprintf('Layer type names "%s" already registeres', $controlType->name()));
        }

        $this->controlTypes[$controlType->name()] = $controlType;
    }

    public function has(string $controlType): bool
    {
        return isset($this->controlTypes[$controlType]);
    }

    public function get(string $controlType): ControlType
    {
        if (! isset($this->controlTypes[$controlType])) {
            throw new InvalidControlType(sprintf('Unknown control type "%s"', $controlType));
        }

        return $this->controlTypes[$controlType];
    }

    public function getIterator(): ControlTypeIterator
    {
        return new ControlTypeIterator(...array_values($this->controlTypes));
    }
}
