<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Provider;

use Cowegis\Bundle\Contao\Model\LayerModel;
use Cowegis\Core\Provider\LayerData;

interface LayerDataProvider
{
    public function findLayerData(LayerModel $layerModel, MapLayerContext $context) : LayerData;
}
