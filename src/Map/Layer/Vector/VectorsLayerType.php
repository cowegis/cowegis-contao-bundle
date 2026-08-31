<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Layer\Vector;

use Cowegis\Bundle\Contao\Map\Layer\LayerType;
use Cowegis\Bundle\Contao\Map\Layer\MapLayerType;
use Cowegis\Bundle\Contao\Model\LayerModel;
use Cowegis\Bundle\Contao\Model\Map\MapLayerModel;
use Cowegis\Core\Definition\Layer\DataLayer;
use Override;

final class VectorsLayerType implements LayerType
{
    use MapLayerType;

    #[Override]
    public function name(): string
    {
        return 'vectors';
    }

    #[Override]
    public function createDefinition(LayerModel $layerModel, MapLayerModel $mapLayerModel): DataLayer
    {
        return new DataLayer(
            $mapLayerModel->layerId(),
            $this->hydrateName($layerModel, $mapLayerModel),
            $this->hydrateInitialVisible($mapLayerModel),
        );
    }

    /** {@inheritDoc} */
    #[Override]
    public function label(string $label, array $row): string
    {
        return $label;
    }
}
