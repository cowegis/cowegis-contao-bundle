<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Layer\Tile;

use Cowegis\Bundle\Contao\Hydrator\Layer\LayerTypeHydrator;
use Cowegis\Bundle\Contao\Model\LayerModel;
use Cowegis\Bundle\Contao\Provider\MapLayerContext;
use Cowegis\Core\Definition\Layer\Layer;

final class TileLayerHydrator extends LayerTypeHydrator
{
    protected const OPTIONS = [
        'minZoom',
        'maxZoom',
        'subdomains',
        'errorTileUrl',
        'zoomOffset',
        'tms',
        'zoomReverse',
        'detectRetina',
        'crossOrigin',
        'attribution',
    ];

    protected function supportedType(): string
    {
        return 'tileLayer';
    }

    protected function hydrateLayer(LayerModel $layerModel, Layer $layer, MapLayerContext $context): void
    {
    }
}
