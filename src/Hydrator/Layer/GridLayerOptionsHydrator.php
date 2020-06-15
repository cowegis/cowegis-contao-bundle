<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Hydrator\Layer;

use Cowegis\Core\Provider\Context;
use Cowegis\Core\Definition\DefinitionId\IntegerDefinitionId;
use Cowegis\Core\Definition\Layer\GridLayer;
use Cowegis\Core\Definition\Map\PaneId;

final class GridLayerOptionsHydrator  extends LayerOptionsHydrator
{
    protected const OPTIONS = [
        'tileSize',
        'opacity',
        'updateWhenIdle',
        'updateWhenZooming',
        'updateInterval',
        'zIndex',
        'bounds',
        'maxNativeZoom',
        'minNativeZoom',
        'noWrap',
        'className',
        'keepBuffer'
    ];

    public function hydrate(object $data, object $definition, Context $context) : void
    {
        parent::hydrate($data, $definition, $context);

        assert($definition instanceof GridLayer);

        if ($data->pane) {
            $definition->options()->set('pane', new PaneId(new IntegerDefinitionId((int) $data->pane)));
        }
    }

    public function supports(object $data, object $definition) : bool
    {
        if (! parent::supports($data, $definition)) {
            return false;
        }

        return $definition instanceof GridLayer;
    }
}