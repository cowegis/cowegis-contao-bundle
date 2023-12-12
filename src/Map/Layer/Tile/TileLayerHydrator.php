<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Layer\Tile;

use Cowegis\Bundle\Contao\Hydrator\Hydrator;
use Cowegis\Bundle\Contao\Map\Layer\LayerTypeHydrator;
use Cowegis\Bundle\Contao\Model\LayerModel;
use Cowegis\Bundle\Contao\Provider\MapLayerContext;
use Cowegis\Core\Definition\Layer\Layer;

/** @psalm-suppress PropertyNotSetInConstructor - see https://github.com/vimeo/psalm/issues/5062 */
final class TileLayerHydrator extends LayerTypeHydrator
{
    /** @var list<string>|array<string,string> */
    protected static array $options = [
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

    protected function hydrateLayer(
        LayerModel $layerModel,
        Layer $layer,
        MapLayerContext $context,
        Hydrator $hydrator,
    ): void {
    }
}
