<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Layer\Reference;

use Cowegis\Bundle\Contao\Hydrator\Hydrator;
use Cowegis\Bundle\Contao\Map\Layer\LayerTypeHydrator;
use Cowegis\Bundle\Contao\Model\LayerModel;
use Cowegis\Bundle\Contao\Model\LayerRepository;
use Cowegis\Bundle\Contao\Provider\MapLayerContext;
use Cowegis\Core\Definition\Layer\Layer;
use Cowegis\Core\Exception\LayerNotFound;

final class ReferenceLayerHydrator extends LayerTypeHydrator
{
    private LayerRepository $layerRepository;

    public function __construct(LayerRepository $layerRepository)
    {
        $this->layerRepository = $layerRepository;
    }

    protected function hydrateLayer(
        LayerModel $layerModel,
        Layer $layer,
        MapLayerContext $context,
        Hydrator $hydrator
    ): void {
        $referenceModel = $this->layerRepository->find((int) $layerModel->reference);
        if ($referenceModel === null) {
            throw LayerNotFound::withLayerId(
                $layerModel->layerId(),
                $context->mapId()
            );
        }

        $hydrator->hydrate($referenceModel, $layer, $context, $hydrator);
    }

    protected function supportedType(): string
    {
        return 'reference';
    }
}
