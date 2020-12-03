<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Hydrator;

use Cowegis\Core\Provider\Context;
use Psr\EventDispatcher\EventDispatcherInterface;

final class EventDispatchingHydrator implements Hydrator
{
    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function supports(object $data, object $definition): bool
    {
        return true;
    }

    public function hydrate(object $data, object $definition, Context $context): void
    {
        $this->eventDispatcher->dispatch(new HydrateEvent($data, $definition, $context));
    }
}
