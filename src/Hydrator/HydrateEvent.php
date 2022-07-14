<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Hydrator;

use Cowegis\Core\Provider\Context;

final class HydrateEvent
{
    private object $data;

    private object $definition;

    private Context $context;

    private Hydrator $hydrator;

    public function __construct(object $data, object $definition, Context $context, Hydrator $hydrator)
    {
        $this->data       = $data;
        $this->definition = $definition;
        $this->context    = $context;
        $this->hydrator   = $hydrator;
    }

    public function data(): object
    {
        return $this->data;
    }

    public function definition(): object
    {
        return $this->definition;
    }

    public function context(): Context
    {
        return $this->context;
    }

    public function hydrator(): Hydrator
    {
        return $this->hydrator;
    }
}
