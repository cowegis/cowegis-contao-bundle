<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Layer\Group;

use Cowegis\Bundle\Contao\Map\Layer\MapLayerType;
use Cowegis\Bundle\Contao\Map\Layer\NodeLayerType;
use Cowegis\Bundle\Contao\Model\LayerModel;
use Cowegis\Bundle\Contao\Model\Map\MapLayerModel;
use Cowegis\Core\Definition\Layer\FeatureGroup;
use Cowegis\Core\Definition\Layer\Layer;
use Cowegis\Core\Definition\Layer\LayerGroup;

final class GroupLayerType implements NodeLayerType
{
    use MapLayerType;

    public function name(): string
    {
        return 'group';
    }

    /** {@inheritDoc} */
    public function label(string $label, array $row): string
    {
        return $label;
    }

    public function createDefinition(LayerModel $layerModel, MapLayerModel $mapLayerModel): Layer
    {
        if ($layerModel->groupType === 'feature') {
            return new FeatureGroup(
                $mapLayerModel->layerId(),
                $this->hydrateName($layerModel, $mapLayerModel),
                $this->hydrateInitialVisible($mapLayerModel),
            );
        }

        return new LayerGroup(
            $mapLayerModel->layerId(),
            $this->hydrateName($layerModel, $mapLayerModel),
            $this->hydrateInitialVisible($mapLayerModel),
        );
    }
}
