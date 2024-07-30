<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map;

use Contao\FilesModel;
use Contao\Model\Collection;
use Contao\StringUtil;
use Cowegis\Bundle\Contao\Hydrator\Hydrator;
use Cowegis\Bundle\Contao\Map\Control\ControlTypeRegistry;
use Cowegis\Bundle\Contao\Map\Icon\IconTypeRegistry;
use Cowegis\Bundle\Contao\Map\Layer\LayerTypeRegistry;
use Cowegis\Bundle\Contao\Model\ControlModel;
use Cowegis\Bundle\Contao\Model\ControlRepository;
use Cowegis\Bundle\Contao\Model\IconModel;
use Cowegis\Bundle\Contao\Model\IconRepository;
use Cowegis\Bundle\Contao\Model\Map\MapLayerModel;
use Cowegis\Bundle\Contao\Model\Map\MapLayerRepository;
use Cowegis\Bundle\Contao\Model\Map\MapModel;
use Cowegis\Bundle\Contao\Model\Map\MapPaneModel;
use Cowegis\Bundle\Contao\Model\Map\MapPaneRepository;
use Cowegis\Bundle\Contao\Model\PopupModel;
use Cowegis\Bundle\Contao\Model\PopupRepository;
use Cowegis\Bundle\Contao\Model\TooltipModel;
use Cowegis\Bundle\Contao\Model\TooltipRepository;
use Cowegis\Bundle\Contao\Provider\MapLayerContext;
use Cowegis\Core\Definition\Asset\Asset;
use Cowegis\Core\Definition\DefinitionId\IntegerDefinitionId;
use Cowegis\Core\Definition\Map\Map;
use Cowegis\Core\Definition\Map\Pane;
use Cowegis\Core\Definition\Map\PaneId;
use Cowegis\Core\Definition\Preset\PopupPreset;
use Cowegis\Core\Definition\Preset\PopupPresetId;
use Cowegis\Core\Definition\Preset\TooltipPreset;
use Cowegis\Core\Definition\Preset\TooltipPresetId;
use Cowegis\Core\Provider\Context;
use Netzmacht\Contao\Toolkit\Data\Model\ContaoRepository;
use Netzmacht\Contao\Toolkit\Data\Model\RepositoryManager;

use function array_map;
use function assert;
use function count;
use function implode;
use function sprintf;

final class MapHydrator implements Hydrator
{
    public function __construct(
        private readonly LayerTypeRegistry $layerTypes,
        private readonly ControlTypeRegistry $controlTypes,
        private readonly IconTypeRegistry $iconTypes,
        private readonly RepositoryManager $repositoryManager,
    ) {
    }

    public function supports(object $data, object $definition): bool
    {
        if (! $data instanceof MapModel) {
            return false;
        }

        return $definition instanceof Map;
    }

    public function hydrate(object $data, object $definition, Context $context, Hydrator $hydrator): void
    {
        assert($data instanceof MapModel);
        assert($definition instanceof Map);

        if ($data->title) {
            $definition->changeTitle($data->title);
        }

        $this->hydratePanes($definition, $data);
        $this->hydrateLayers($definition, $data, $context, $hydrator);
        $this->hydrateControls($definition, $data, $context, $hydrator);
        $this->hydrateLocate($definition, $data);
        $this->hydrateIconPresets($definition, $context, $hydrator);
        $this->hydratePopupPresets($definition, $context, $hydrator);
        $this->hydrateTooltipPresets($definition, $context, $hydrator);
        $this->hydrateAssets($definition, $data, $context);
        $hydrator->hydrate($data, $definition->view(), $context, $hydrator);
    }

    private function hydratePanes(Map $map, MapModel $mapModel): void
    {
        $repository = $this->repositoryManager->getRepository(MapPaneModel::class);
        assert($repository instanceof MapPaneRepository);
        $collection = $repository->findByMap((int) $mapModel->id) ?? [];

        foreach ($collection as $paneModel) {
            assert($paneModel instanceof MapPaneModel);
            $map->panes()->add(
                new Pane(
                    PaneId::fromValue(new IntegerDefinitionId((int) $paneModel->id)),
                    $paneModel->name,
                    $paneModel->zIndex === null ? null : ((int) $paneModel->zIndex),
                    $paneModel->pointerEvents,
                ),
            );
        }
    }

    private function hydrateLayers(Map $map, MapModel $mapModel, Context $context, Hydrator $hydrator): void
    {
        $repository = $this->repositoryManager->getRepository(MapLayerModel::class);
        assert($repository instanceof MapLayerRepository);
        $collection = $repository->findActive((int) $mapModel->id) ?: [];

        foreach ($collection as $mapLayerModel) {
            assert($mapLayerModel instanceof MapLayerModel);
            $layerModel = $mapLayerModel->layerModel();
            $layerType  = $this->layerTypes->get($layerModel->type);
            $definition = $layerType->createDefinition($layerModel, $mapLayerModel);
            $paneId     = $mapLayerModel->pane > 0 ? PaneId::fromValue(
                IntegerDefinitionId::fromValue((int) $mapLayerModel->pane),
            ) : null;
            $dataPaneId = $mapLayerModel->dataPane > 0 ? PaneId::fromValue(
                IntegerDefinitionId::fromValue((int) $mapLayerModel->dataPane),
            ) : null;

            $layerContext = new MapLayerContext($context, $mapLayerModel, $paneId, $dataPaneId);

            $hydrator->hydrate($layerModel, $definition, $layerContext, $hydrator);
            $definition->addTo($map);
        }
    }

    private function hydrateControls(Map $map, MapModel $mapModel, Context $context, Hydrator $hydrator): void
    {
        $repository = $this->repositoryManager->getRepository(ControlModel::class);
        assert($repository instanceof ControlRepository);
        $collection = $repository->findActive((int) $mapModel->id) ?: [];

        foreach ($collection as $controlModel) {
            assert($controlModel instanceof ControlModel);
            $controlType = $this->controlTypes->get($controlModel->type);
            $definition  = $controlType->createDefinition($controlModel);

            $hydrator->hydrate($controlModel, $definition, $context, $hydrator);
            $definition->addTo($map);
        }
    }

    private function hydrateIconPresets(Map $map, Context $context, Hydrator $hydrator): void
    {
        $repository = $this->repositoryManager->getRepository(IconModel::class);
        assert($repository instanceof IconRepository);
        $collection = $repository->findAllActive() ?: [];

        foreach ($collection as $model) {
            assert($model instanceof IconModel);
            if (! $this->iconTypes->has($model->type)) {
                continue;
            }

            $preset = $this->iconTypes->get($model->type)->createDefinition($model);

            $hydrator->hydrate($model, $preset, $context, $hydrator);
            $map->presets()->addIcon($preset);
        }
    }

    private function hydratePopupPresets(Map $map, Context $context, Hydrator $hydrator): void
    {
        $repository = $this->repositoryManager->getRepository(PopupModel::class);
        assert($repository instanceof PopupRepository);
        $collection = $repository->findAllActive() ?: [];

        foreach ($collection as $model) {
            assert($model instanceof PopupModel);
            $presetId = PopupPresetId::fromValue(IntegerDefinitionId::fromValue((int) $model->id));
            $preset   = new PopupPreset($presetId);

            $hydrator->hydrate($model, $preset, $context, $hydrator);
            $map->presets()->addPopup($preset);
        }
    }

    private function hydrateTooltipPresets(Map $map, Context $context, Hydrator $hydrator): void
    {
        $repository = $this->repositoryManager->getRepository(TooltipModel::class);
        assert($repository instanceof TooltipRepository);
        $collection = $repository->findAll();

        if (! $collection instanceof Collection) {
            return;
        }

        foreach ($collection as $model) {
            assert($model instanceof TooltipModel);
            $presetId = TooltipPresetId::fromValue(IntegerDefinitionId::fromValue((int) $model->id));
            $preset   = new TooltipPreset($presetId);

            $hydrator->hydrate($model, $preset, $context, $hydrator);
            $map->presets()->addTooltip($preset);
        }
    }

    private function hydrateLocate(Map $map, MapModel $model): void
    {
        if (! (bool) $model->locate) {
            return;
        }

        $map->enableLocate();
    }

    /** @SuppressWarnings(PHPMD.UnusedFormalParameter) */
    private function hydrateAssets(Map $definition, MapModel $model, Context $context): void
    {
        if ($model->defaultAssets) {
            $context->assets()->add(Asset::STYLESHEET('bundles/cowegisclient/css/cowegis.css'));
        }

        $uuids      = StringUtil::deserialize($model->assets, true);
        $order      = StringUtil::deserialize($model->assetsOrder, true);
        $repository = $this->repositoryManager->getRepository(FilesModel::class);
        $options    = [];

        if (count($order) > 1) {
            $options = [
                'order' => sprintf(
                    'FIELD(uuid, UNHEX(\'%s\'))',
                    implode('\'), UNHEX(\'', array_map('bin2hex', $order)),
                ),
            ];
        }

        assert($repository instanceof ContaoRepository);
        $collection = $repository->findMultipleByUuids($uuids, $options) ?: [];

        foreach ($collection as $fileModel) {
            assert($fileModel instanceof FilesModel);
            if ($fileModel->type !== 'file') {
                continue;
            }

            if ($fileModel->extension === 'js') {
                $context->assets()->add(Asset::JAVASCRIPT($fileModel->path));
                continue;
            }

            if ($fileModel->extension !== 'css') {
                continue;
            }

            $context->assets()->add(Asset::STYLESHEET($fileModel->path));
        }
    }
}
