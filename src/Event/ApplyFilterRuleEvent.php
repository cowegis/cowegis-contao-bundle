<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Event;

use Cowegis\Core\Filter\Rule;

final class ApplyFilterRuleEvent
{
    /**
     * @param list<string>        $columns
     * @param list<mixed>         $values
     * @param array<string,mixed> $options
     */
    public function __construct(
        private readonly string $modelClass,
        private readonly Rule $rule,
        private array $columns,
        private array $values,
        private readonly array $options = [],
    ) {
    }

    /** @return list<string> */
    public function columns(): array
    {
        return $this->columns;
    }

    /** @return list<mixed> */
    public function values(): array
    {
        return $this->values;
    }

    public function modelClass(): string
    {
        return $this->modelClass;
    }

    /** @return array<string,mixed> */
    public function options(): array
    {
        return $this->options;
    }

    public function rule(): Rule
    {
        return $this->rule;
    }

    public function withColumns(string ...$columns): void
    {
        foreach ($columns as $column) {
            $this->columns[] = $column;
        }
    }

    /** @param mixed ...$values */
    public function withValues(...$values): void
    {
        foreach ($values as $value) {
            $this->values[] = $value;
        }
    }
}
