<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Layer\Vector;

use Cowegis\Bundle\Contao\Model\LayerModel;
use Cowegis\Bundle\Contao\Provider\LayerDataProvider;
use Cowegis\Bundle\Contao\Provider\MapLayerContext;
use Cowegis\Core\Exception\InvalidArgument;
use Cowegis\Core\Provider\LayerData;

use function sprintf;

final class VectorsDataLayerProvider implements LayerDataProvider
{
    public function findLayerData(LayerModel $layerModel, MapLayerContext $context): LayerData
    {
        if ($layerModel->type !== 'vectors') {
            throw new InvalidArgument(sprintf('Unsupported layer type "%s"', $layerModel->type));
        }

        return new VectorsLayerData($layerModel->vectors);
    }
}
