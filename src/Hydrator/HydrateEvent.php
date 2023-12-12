<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Hydrator;

use Cowegis\Core\Provider\Context;

final class HydrateEvent
{
    public function __construct(
        private readonly object $data,
        private readonly object $definition,
        private readonly Context $context,
        private readonly Hydrator $hydrator,
    ) {
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
