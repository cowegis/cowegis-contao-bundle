<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Layer;

use Cowegis\Bundle\Contao\Model\LayerModel;
use Cowegis\Bundle\Contao\Model\Map\MapLayerModel;
use Cowegis\Core\Definition\Layer\Layer;

interface LayerType
{
    public function name() : string;

    public function createDefinition(LayerModel $layerModel, MapLayerModel $mapLayerModel) : Layer;

    public function label(string $label, array $row) : string;

    public function iconUrl() : string;
}
