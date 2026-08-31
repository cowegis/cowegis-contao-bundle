<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Layer\MarkerCluster;

use Cowegis\Bundle\Contao\Map\Layer\MapLayerType;
use Cowegis\Bundle\Contao\Map\Layer\NodeLayerType;
use Cowegis\Bundle\Contao\Model\LayerModel;
use Cowegis\Bundle\Contao\Model\Map\MapLayerModel;
use Cowegis\Core\Definition\Layer\MarkerClusterGroup;
use Override;

final class MarkerClusterGroupType implements NodeLayerType
{
    use MapLayerType;

    #[Override]
    public function name(): string
    {
        return 'markerCluster';
    }

    /** {@inheritDoc} */
    #[Override]
    public function label(string $label, array $row): string
    {
        return $label;
    }

    #[Override]
    public function createDefinition(LayerModel $layerModel, MapLayerModel $mapLayerModel): MarkerClusterGroup
    {
        return new MarkerClusterGroup(
            $mapLayerModel->layerId(),
            $this->hydrateName($layerModel, $mapLayerModel),
            $this->hydrateInitialVisible($mapLayerModel),
        );
    }
}
