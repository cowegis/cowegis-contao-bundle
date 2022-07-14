<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Hydrator;

use Cowegis\Core\Provider\Context;

final class DelegatingHydrator implements Hydrator
{
    /** @var Hydrator[] */
    private array $hydrators;

    /** @param Hydrator[] $hydrators */
    public function __construct(iterable $hydrators)
    {
        $this->hydrators = $hydrators;
    }

    public function supports(object $data, object $definition): bool
    {
        foreach ($this->hydrators as $hydrator) {
            if ($hydrator->supports($data, $definition)) {
                return true;
            }
        }

        return false;
    }

    public function hydrate(object $data, object $definition, Context $context): void
    {
        foreach ($this->hydrators as $hydrator) {
            if (! $hydrator->supports($data, $definition)) {
                continue;
            }

            $hydrator->hydrate($data, $definition, $context);
        }
    }
}
