<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Provider;

use Contao\CoreBundle\Framework\ContaoFramework;
use Cowegis\Bundle\Contao\Hydrator\Hydrator;
use Cowegis\Bundle\Contao\Model\Map\MapLayerRepository;
use Cowegis\Bundle\Contao\Model\Map\MapRepository;
use Cowegis\Core\Definition\DefinitionId\IntegerDefinitionId;
use Cowegis\Core\Definition\Layer\LayerId;
use Cowegis\Core\Definition\Map\Map;
use Cowegis\Core\Definition\Map\MapId;
use Cowegis\Core\Definition\Map\PaneId;
use Cowegis\Core\Exception\InvalidArgument;
use Cowegis\Core\Exception\LayerNotFound;
use Cowegis\Core\Exception\MapNotFound;
use Cowegis\Core\IdFormat\IdFormat;
use Cowegis\Core\Provider\Context;
use Cowegis\Core\Provider\LayerData;
use Cowegis\Core\Provider\Provider;
use Psr\Container\ContainerInterface;

use function assert;
use function sprintf;

final class ContaoBackendProvider implements Provider
{
    /** @var MapRepository */
    private $mapRepository;

    /** @var MapLayerRepository */
    private $mapLayerRepository;

    /** @var Hydrator */
    private $hydrator;

    /** @var ContaoFramework */
    private $framework;

    /** @var ContainerInterface */
    private $layerDataProviders;

    /** @var IdFormat */
    private $idFormat;

    public function __construct(
        ContaoFramework $framework,
        MapRepository $mapRepository,
        MapLayerRepository $layerRepository,
        Hydrator $hydrator,
        ContainerInterface $layerDataProviders,
        IdFormat $idFormat
    ) {
        $this->mapRepository      = $mapRepository;
        $this->mapLayerRepository = $layerRepository;
        $this->hydrator           = $hydrator;
        $this->framework          = $framework;
        $this->layerDataProviders = $layerDataProviders;
        $this->idFormat           = $idFormat;
    }

    public function idFormat(): IdFormat
    {
        return $this->idFormat;
    }

    public function findMap(MapId $mapId, Context $context): Map
    {
        $this->initialize($context);

        // TODO: Protect against unsupported id formats

        $mapModel = $this->mapRepository->find((int) $mapId->value());
        if ($mapModel === null) {
            throw MapNotFound::withMapId($mapId);
        }

        $definition = new Map(
            MapId::fromValue(new IntegerDefinitionId((int) $mapModel->id)),
            $mapModel->alias ?: 'map_' . $mapModel->id
        );

        $this->hydrator->hydrate($mapModel, $definition, $context);

        return $definition;
    }

    public function findLayerData(MapId $mapId, LayerId $layerId, Context $context): LayerData
    {
        $this->initialize($context);

        // TODO: Protect against unsupported id formats

        $mapLayer = $this->mapLayerRepository->findActiveLayer((int) $mapId->value(), (int) $layerId->value());
        if ($mapLayer === null) {
            throw LayerNotFound::withLayerId($layerId, $mapId);
        }

        if (! $this->layerDataProviders->has($mapLayer->type)) {
            throw new InvalidArgument(sprintf('Unsupported layer type "%s".', $mapLayer->type));
        }

        $layerDataProvider = $this->layerDataProviders->get($mapLayer->type);
        assert($layerDataProvider instanceof LayerDataProvider);

        $paneId     = $mapLayer->pane > 0 ? $this->idFormat->createDefinitionId(PaneId::class, $mapLayer->pane) : null;
        $dataPaneId = $mapLayer->dataPane > 0
            ? $this->idFormat->createDefinitionId(PaneId::class, $mapLayer->dataPane)
            : null;

        // Psalm does not detect correct type even though the template syntax is correct
        /** @psalm-suppress ArgumentTypeCoercion */
        $layerContext = new MapLayerContext($context, $mapLayer, $paneId, $dataPaneId);

        return $layerDataProvider->findLayerData($mapLayer->layerModel(), $layerContext);
    }

    /** @SuppressWarnings(PHPMD.Superglobals) */
    private function initialize(Context $context): void
    {
        $this->framework->initialize();

        if (! $context->locale()) {
            return;
        }

        $GLOBALS['TL_LANGUAGE'] = $context->locale();
    }
}
