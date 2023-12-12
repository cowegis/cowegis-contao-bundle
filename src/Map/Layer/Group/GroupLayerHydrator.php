<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Layer\Group;

use Cowegis\Bundle\Contao\Hydrator\Hydrator;
use Cowegis\Bundle\Contao\Map\Layer\LayerTypeHydrator;
use Cowegis\Bundle\Contao\Model\LayerModel;
use Cowegis\Bundle\Contao\Model\Map\MapLayerModel;
use Cowegis\Bundle\Contao\Model\Map\MapLayerRepository;
use Cowegis\Bundle\Contao\Provider\MapLayerContext;
use Cowegis\Core\Definition\Layer\Layer;
use Cowegis\Core\Definition\Layer\LayerGroup;
use Netzmacht\Contao\Toolkit\Response\ResponseTagger;

use function assert;

class GroupLayerHydrator extends LayerTypeHydrator
{
    public function __construct(private readonly MapLayerRepository $repository, ResponseTagger $responseTagger)
    {
        parent::__construct($responseTagger);
    }

    protected function hydrateLayer(
        LayerModel $layerModel,
        Layer $layer,
        MapLayerContext $context,
        Hydrator $hydrator,
    ): void {
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
