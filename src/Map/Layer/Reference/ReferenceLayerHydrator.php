<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Layer\Reference;

use Cowegis\Bundle\Contao\Hydrator\Hydrator;
use Cowegis\Bundle\Contao\Hydrator\Layer\LayerTypeHydrator;
use Cowegis\Bundle\Contao\Provider\MapLayerContext;
use Cowegis\Bundle\Contao\Model\LayerModel;
use Cowegis\Bundle\Contao\Model\LayerRepository;
use Cowegis\Core\Definition\Layer\Layer;
use Cowegis\Core\Exception\LayerNotFound;

final class ReferenceLayerHydrator extends LayerTypeHydrator
{
    /** @var Hydrator */
    private $hydrator;

    /** @var LayerRepository */
    private $layerRepository;

    public function __construct(Hydrator $hydrator, LayerRepository $layerRepository)
    {
        $this->hydrator        = $hydrator;
        $this->layerRepository = $layerRepository;
    }

    protected function hydrateLayer(LayerModel $layerModel, Layer $layer, MapLayerContext $context) : void
    {
        $referenceModel = $this->layerRepository->find((int) $layerModel->reference);
        if ($referenceModel === null) {
            throw LayerNotFound::withLayerId(
                $layerModel->layerId(),
                $context->mapId()
            );
        }

        $this->hydrator->hydrate($referenceModel, $layer, $context);
    }

    protected function supportedType() : string
    {
        return 'reference';
    }
}
