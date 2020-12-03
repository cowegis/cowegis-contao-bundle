<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Style;

use Cowegis\Bundle\Contao\Exception\InvalidStyleType;
use IteratorAggregate;

use function array_values;
use function sprintf;

final class StyleTypeRegistry implements IteratorAggregate
{
    /** @var array<string, StyleType> */
    private $controlTypes = [];

    /**
     * @param iterable|StyleType[] $controlTypes
     */
    public function __construct(iterable $controlTypes)
    {
        foreach ($controlTypes as $controlType) {
            $this->register($controlType);
        }
    }

    public function register(StyleType $controlType): void
    {
        if (isset($this->controlTypes[$controlType->name()])) {
            throw new InvalidStyleType(sprintf('Layer type names "%s" already registeres', $controlType->name()));
        }

        $this->controlTypes[$controlType->name()] = $controlType;
    }

    public function has(string $controlType): bool
    {
        return isset($this->controlTypes[$controlType]);
    }

    public function get(string $controlType): StyleType
    {
        if (! isset($this->controlTypes[$controlType])) {
            throw new InvalidStyleType(sprintf('Unknown control type "%s"', $controlType));
        }

        return $this->controlTypes[$controlType];
    }

    public function getIterator(): StyleTypeIterator
    {
        return new StyleTypeIterator(...array_values($this->controlTypes));
    }
}
