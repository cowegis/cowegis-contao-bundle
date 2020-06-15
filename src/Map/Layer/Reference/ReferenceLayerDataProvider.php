<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Layer\Reference;

use Cowegis\Bundle\Contao\Model\LayerModel;
use Cowegis\Bundle\Contao\Model\LayerRepository;
use Cowegis\Bundle\Contao\Provider\LayerDataProvider;
use Cowegis\Bundle\Contao\Provider\MapLayerContext;
use Cowegis\Core\Exception\RuntimeException;
use Cowegis\Core\Provider\LayerData;
use Psr\Container\ContainerInterface;

final class ReferenceLayerDataProvider implements LayerDataProvider
{
    /** @var LayerRepository */
    private $layerRepository;

    /** @var ContainerInterface */
    private $layerDataProviders;

    public function __construct(LayerRepository $layerRepository, ContainerInterface $layerDataProviders)
    {
        $this->layerRepository    = $layerRepository;
        $this->layerDataProviders = $layerDataProviders;
    }

    public function findLayerData(LayerModel $layerModel, MapLayerContext $context) : LayerData
    {
        if ($layerModel->type !== 'reference') {
            // TODO: Exception
            throw new RuntimeException();
        }

        $referenceLayer = $this->layerRepository->find((int) $layerModel->reference);
        if (! $referenceLayer instanceof LayerModel) {
            // TODO: Exception
            throw new RuntimeException();
        }

        if (! $this->layerDataProviders->has($referenceLayer->type)) {
            // TODO: Exception
            throw new RuntimeException();
        }

        /** @var LayerDataProvider $layerDataProvider */
        $layerDataProvider = $this->layerDataProviders->get($referenceLayer->type);

        return $layerDataProvider->findLayerData($referenceLayer, $context);
    }
}