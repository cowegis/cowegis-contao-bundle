<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Layer\Markers;

use Contao\StringUtil;
use Cowegis\Bundle\Contao\Hydrator\Hydrator;
use Cowegis\Bundle\Contao\Map\Layer\Markers\Hydrator\MarkerContext;
use Cowegis\Bundle\Contao\Model\LayerModel;
use Cowegis\Bundle\Contao\Model\Map\MapLayerModel;
use Cowegis\Bundle\Contao\Model\MarkerModel;
use Cowegis\Bundle\Contao\Model\MarkerRepository;
use Cowegis\Bundle\Contao\Provider\LayerDataProvider;
use Cowegis\Bundle\Contao\Provider\MapLayerContext;
use Cowegis\Core\Definition\DefinitionId\IntegerDefinitionId;
use Cowegis\Core\Definition\Icon\IconId;
use Cowegis\Core\Definition\LatLng;
use Cowegis\Core\Definition\Map\PaneId;
use Cowegis\Core\Definition\Preset\PopupPresetId;
use Cowegis\Core\Definition\Preset\TooltipPresetId;
use Cowegis\Core\Definition\UI\Marker;
use Cowegis\Core\Definition\UI\MarkerId;
use Cowegis\Core\Exception\InvalidArgument;
use Cowegis\Core\Provider\LayerData;
use Cowegis\Core\Provider\LayerData\MarkersLayerData;

final class MarkersLayerDataProvider implements LayerDataProvider
{
    /** @var MarkerRepository */
    private $markersRepository;

    /** @var Hydrator */
    private $hydrator;

    public function __construct(MarkerRepository $markersRepository, Hydrator $hydrator)
    {
        $this->markersRepository = $markersRepository;
        $this->hydrator          = $hydrator;
    }

    public function findLayerData(LayerModel $layerModel, MapLayerContext $context) : LayerData
    {
        if ($layerModel->type !== 'markers') {
            throw new InvalidArgument(sprintf('Unsupported layer type "%s"', $layerModel->type));
        }

        // TODO: Only pass filter if activated
        $filter     = $context->filter();
        $options    = ['filter' => $filter, 'rules' => StringUtil::deserialize($layerModel->filterRules, true)];
        $collection = $this->markersRepository->findActiveByLayer((int) $layerModel->layerId()->value(), $options) ?: [];
        $markers    = [];
        $context    = $this->determineLocalContext($layerModel, $context);

        /** @var MarkerModel $markerModel */
        foreach ($collection as $markerModel) {
            $marker = new Marker(
                MarkerId::fromValue(IntegerDefinitionId::fromValue((int) $markerModel->id())),
                $markerModel->alias ?: 'marker_' . $markerModel->id,
                new LatLng(
                    (float) $markerModel->latitude,
                    (float) $markerModel->longitude,
                    $markerModel->altitude > 0 ? ((float) $markerModel->altitude) : null
                )
            );

            $this->hydrator->hydrate($layerModel, $marker, $context);
            $this->hydrator->hydrate($markerModel, $marker, $context);
            $markers[] = $marker;
        }

        return new MarkersLayerData($markers, $context->assets(), $context->callbacks());
    }

    private function determineLocalContext(
        LayerModel $layerModel,
        MapLayerContext $context
    ) : MarkerContext {
        $popupPresetId   = null;
        $iconId          = null;
        $tooltipPresetId = null;

        if ($layerModel->popup > 0) {
            $popupPresetId = PopupPresetId::fromValue(IntegerDefinitionId::fromValue((int) $layerModel->popup));
        }

        if ($layerModel->icon > 0) {
            $iconId = IconId::fromValue(IntegerDefinitionId::fromValue((int) $layerModel->icon));
        }

        if ($layerModel->tooltip > 0) {
            $tooltipPresetId = TooltipPresetId::fromValue(IntegerDefinitionId::fromValue((int) $layerModel->tooltip));
        }

        return new MarkerContext(
            $context,
            $iconId,
            $popupPresetId,
            $tooltipPresetId
        );
    }
}
