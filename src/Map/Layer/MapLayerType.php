<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Layer;

use Cowegis\Bundle\Contao\Model\LayerModel;
use Cowegis\Bundle\Contao\Model\Map\MapLayerModel;

use function sprintf;

trait MapLayerType
{
    protected function hydrateName(LayerModel $layerModel, MapLayerModel $mapLayerModel): string
    {
        return $layerModel->alias ?: 'layer_' . $mapLayerModel->id;
    }

    protected function hydrateInitialVisible(MapLayerModel $mapLayerModel): bool
    {
        return (bool) $mapLayerModel->initialVisible;
    }

    public function iconUrl(): string
    {
        return sprintf('bundles/cowegiscontao/img/%s.png', $this->name());
    }
}
