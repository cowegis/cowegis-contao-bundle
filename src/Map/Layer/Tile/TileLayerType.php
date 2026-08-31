<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Layer\Tile;

use Cowegis\Bundle\Contao\Map\Layer\LayerType;
use Cowegis\Bundle\Contao\Map\Layer\MapLayerType;
use Cowegis\Bundle\Contao\Model\LayerModel;
use Cowegis\Bundle\Contao\Model\Map\MapLayerModel;
use Cowegis\Core\Definition\Layer\TileLayer;
use Override;

use function is_string;
use function parse_url;
use function preg_replace;
use function sprintf;

use const PHP_URL_HOST;

final class TileLayerType implements LayerType
{
    use MapLayerType;

    #[Override]
    public function name(): string
    {
        return 'tileLayer';
    }

    /** {@inheritDoc} */
    #[Override]
    public function label(string $label, array $row): string
    {
        $url = parse_url((string) $row['tileUrl'], PHP_URL_HOST);
        if (is_string($url)) {
            $url    = (string) preg_replace('#\{s\}\.#', '', $url);
            $label .= sprintf(' <span class="tl_gray">(%s)</span>', $url);
        }

        return $label;
    }

    #[Override]
    public function createDefinition(LayerModel $layerModel, MapLayerModel $mapLayerModel): TileLayer
    {
        return new TileLayer(
            $mapLayerModel->layerId(),
            $this->hydrateName($layerModel, $mapLayerModel),
            $layerModel->tileUrl,
            $this->hydrateInitialVisible($mapLayerModel),
        );
    }
}
