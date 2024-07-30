<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Layer\Overpass;

use Contao\StringUtil;
use Cowegis\Bundle\Contao\Map\Layer\LayerType;
use Cowegis\Bundle\Contao\Map\Layer\MapLayerType;
use Cowegis\Bundle\Contao\Model\LayerModel;
use Cowegis\Bundle\Contao\Model\Map\MapLayerModel;
use Cowegis\Core\Definition\Layer\Layer;
use Cowegis\Core\Definition\Layer\OverpassLayer;

final class OverpassLayerType implements LayerType
{
    use MapLayerType;

    public function name(): string
    {
        return 'overpass';
    }

    public function createDefinition(LayerModel $layerModel, MapLayerModel $mapLayerModel): Layer
    {
        return new OverpassLayer(
            $layerModel->layerId(),
            $this->hydrateName($layerModel, $mapLayerModel),
            $this->hydrateInitialVisible($mapLayerModel),
        );
    }

    /** {@inheritDoc} */
    public function label(string $label, array $row): string
    {
        if ($row['overpassQuery'] !== null) {
            $label .= '<span class="tl_gray"> ' . StringUtil::substr($row['overpassQuery'], 50) . '</span>';
        }

        return $label;
    }
}
