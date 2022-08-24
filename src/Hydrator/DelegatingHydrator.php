<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Hydrator;

use Cowegis\Core\Provider\Context;
use Netzmacht\Contao\Toolkit\Response\ResponseTagger;

/** @psalm-suppress PropertyNotSetInConstructor - see https://github.com/vimeo/psalm/issues/5062 */
final class DelegatingHydrator implements Hydrator
{
    use ResponseTaggerPlugin {
        ResponseTaggerPlugin::__construct as private responseTaggerConstruct;
    }

    /** @var Hydrator[] */
    private iterable $hydrators;

    /** @param Hydrator[] $hydrators */
    public function __construct(iterable $hydrators, ResponseTagger $responseTagger)
    {
        $this->responseTaggerConstruct($responseTagger);

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

    public function hydrate(object $data, object $definition, Context $context, Hydrator $hydrator): void
    {
        foreach ($this->hydrators as $delegate) {
            if (! $delegate->supports($data, $definition)) {
                continue;
            }

            $this->tagResponseForData($data);
            $delegate->hydrate($data, $definition, $context, $hydrator);
        }
    }
}
