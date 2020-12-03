<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Hydrator\Options;

use Cowegis\Bundle\Contao\Model\Map\MapModel;
use Cowegis\Core\Definition\Map\Map;
use Cowegis\Core\Definition\Options;
use Cowegis\Core\Provider\Context;

use function assert;

final class LocateOptionsHydrator extends ConfigurableOptionsHydrator
{
    protected const OPTIONS = [
        'setView'            => 'locateSetView',
        'watch'              => 'locateWatch',
        'enableHighAccuracy' => 'enableHighAccuracy',
        'maxZoom'            => 'locateMaxZoom',
        'timeout'            => 'locateTimeout',
        'maximumAge'         => 'locateMaximumAge',
    ];

    public function hydrate(object $data, object $definition, Context $context): void
    {
        assert($definition instanceof Map);

        if (! $definition->locate()) {
            return;
        }

        parent::hydrate($data, $definition, $context);
    }

    protected function determineOptions(object $definition): Options
    {
        assert($definition instanceof Map);

        return $definition->locateOptions();
    }

    protected function supportsDefinition(object $definition): bool
    {
        return $definition instanceof Map;
    }

    protected function supportsData(object $data): bool
    {
        return $data instanceof MapModel;
    }
}
