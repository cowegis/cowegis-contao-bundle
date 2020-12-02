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
     * ApplyFilterRuleEvent constructor.
     *
     * @param string   $modelClass
     * @param Rule     $rule
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

    public function columns() : array
    {
        return $this->columns;
    }

    public function values() : array
    {
        return $this->values;
    }

    public function modelClass() : string
    {
        return $this->modelClass;
    }

    public function options() : array
    {
        return $this->options;
    }

    public function rule() : Rule
    {
        return $this->rule;
    }

    public function withColumns(string ...$columns) : void
    {
        foreach ($columns as $column) {
            $this->columns[] = $column;
        }
    }

    public function withValues(...$values) : void
    {
        foreach ($values as $value) {
            $this->values[] = $value;
        }
    }
}
