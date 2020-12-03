<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Event;

use Cowegis\Core\Filter\Rule;

final class ApplyFilterRuleEvent
{
    /** @var string */
    private $modelClass;

    /** @var Rule */
    private $rule;

    /** @var string[] */
    private $columns;

    /** @var mixed[] */
    private $values;

    /**
     * @var mixed[]
     */
    private $options;

    /**
     * @param string[] $columns
     * @param mixed[]  $values
     * @param mixed[]  $options
     */
    public function __construct(string $modelClass, Rule $rule, array $columns, array $values, array $options = [])
    {
        $this->modelClass = $modelClass;
        $this->rule       = $rule;
        $this->columns    = $columns;
        $this->values     = $values;
        $this->options    = $options;
    }

    /** @return string[] */
    public function columns(): array
    {
        return $this->columns;
    }

    /** @return string[] */
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
