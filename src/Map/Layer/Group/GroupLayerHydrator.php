<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Layer\Group;

use Cowegis\Bundle\Contao\Hydrator\Layer\LayerTypeHydrator;
use Cowegis\Bundle\Contao\Model\LayerModel;
use Cowegis\Bundle\Contao\Model\Map\MapLayerModel;
use Cowegis\Bundle\Contao\Model\Map\MapLayerRepository;
use Cowegis\Bundle\Contao\Provider\MapLayerContext;
use Cowegis\Core\Definition\Layer\Layer;
use Cowegis\Core\Definition\Layer\LayerGroup;

use function assert;

class GroupLayerHydrator extends LayerTypeHydrator
{
    /** @var MapLayerRepository */
    private $repository;

    public function __construct(MapLayerRepository $repository)
    {
        $this->repository = $repository;
    }

    protected function hydrateLayer(LayerModel $layerModel, Layer $layer, MapLayerContext $context): void
    {
        assert($layer instanceof LayerGroup);

        $children = $this->repository->findChildren((int) $layerModel->layerId()->value()) ?: [];
        foreach ($children as $child) {
            assert($child instanceof MapLayerModel);
            $layer->layers()->addLayer($child->layerId());
        }
    }

    protected function supportedType(): string
    {
        return 'group';
    }
}
