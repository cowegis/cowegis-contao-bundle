<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Layer;

use Cowegis\Bundle\Contao\Map\Options\ConfigurableOptionsHydrator;
use Cowegis\Bundle\Contao\Model\Map\MapLayerModel;
use Cowegis\Core\Definition\LayerObject;

abstract class LayerOptionsHydrator extends ConfigurableOptionsHydrator
{
    protected function supportsData(object $data): bool
    {
        return $data instanceof MapLayerModel;
    }

    protected function supportsDefinition(object $definition): bool
    {
        return $definition instanceof LayerObject;
    }
}
