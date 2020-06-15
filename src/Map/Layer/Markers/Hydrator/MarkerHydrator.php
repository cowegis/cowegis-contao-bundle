<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Layer\Markers\Hydrator;

use Cowegis\Bundle\Contao\Hydrator\Hydrator;
use Cowegis\Bundle\Contao\Hydrator\Options\ConfigurableOptionsHydrator;
use Cowegis\Bundle\Contao\Map\Icon\IconTypeRegistry;
use Cowegis\Bundle\Contao\Model\IconModel;
use Cowegis\Bundle\Contao\Model\IconRepository;
use Cowegis\Bundle\Contao\Provider\MapLayerContext;
use Cowegis\Bundle\ContaoMultilingual\Model\MarkerModel;
use Cowegis\Core\Definition\DefinitionId\IntegerDefinitionId;
use Cowegis\Core\Definition\Icon\IconId;
use Cowegis\Core\Definition\Preset\PopupPresetId;
use Cowegis\Core\Definition\Preset\TooltipPresetId;
use Cowegis\Core\Definition\UI\Marker;
use Cowegis\Core\Definition\UI\Popup;
use Cowegis\Core\Definition\UI\Tooltip;
use Cowegis\Core\Provider\Context;
use function assert;
use function is_array;
use function json_decode;

final class MarkerHydrator extends ConfigurableOptionsHydrator
{
    protected const OPTIONS = [
        'alt',
        'title' => 'tooltip'
    ];

    /** @var IconTypeRegistry */
    private $iconTypes;

    /** @var IconRepository */
    private $iconRepository;

    /** @var Hydrator */
    private $hydrator;

    public function __construct(IconTypeRegistry $iconTypes, IconRepository $iconRepository, Hydrator $hydrator)
    {
        $this->iconTypes      = $iconTypes;
        $this->iconRepository = $iconRepository;
        $this->hydrator       = $hydrator;
    }

    public function hydrate(object $markerModel, object $definition, Context $context) : void
    {
        assert($markerModel instanceof MarkerModel);
        assert($definition instanceof Marker);

        parent::hydrate($markerModel, $definition, $context);

        $definition->changeTitle($markerModel->title);

        $this->hydrateIcon($markerModel, $definition, $context);
        $this->hydratePopup($markerModel, $definition, $context);
        $this->hydrateTooltip($markerModel, $definition, $context);

        if ($markerModel->featureData) {
            $data = json_decode($markerModel->featureData, true);
            if (is_array($data)) {
                $definition->properties()->merge($data);
            }
        }

        if (($context instanceof MarkerContext || $context instanceof MapLayerContext) && $context->dataPaneId()) {
            $definition->options()->set('pane', $context->dataPaneId());
        }
    }

    protected function supportsDefinition(object $definition) : bool
    {
        return $definition instanceof Marker;
    }

    protected function supportsData(object $data) : bool
    {
        return $data instanceof MarkerModel;
    }

    private function hydratePopup(MarkerModel $markerModel, Marker $definition, Context $context) : void
    {
        if (! $markerModel->addPopup) {
            return;
        }

        $presetId = null;
        if ($context instanceof MarkerContext) {
            $presetId = $context->popupPresetId();
        }
        if($markerModel->popup > 0) {
            $presetId = PopupPresetId::fromValue(IntegerDefinitionId::fromValue((int) $markerModel->popup));
        }

        $definition->openPopup(new Popup($markerModel->popupContent, $presetId));
    }

    private function hydrateTooltip(MarkerModel $markerModel, Marker $definition, Context $context) : void
    {
        if (! $markerModel->addTooltip) {
            return;
        }

        $presetId = null;
        if ($context instanceof MarkerContext) {
            $presetId = $context->tooltipPresetId();
        }
        if($markerModel->tooltipPreset > 0) {
            $presetId = TooltipPresetId::fromValue(IntegerDefinitionId::fromValue((int) $markerModel->tooltipPreset));
        }

        $toolTip = New Tooltip($markerModel->tooltipContent, null, $presetId);

        $definition->showTooltip($toolTip);
    }

    private function hydrateIcon(MarkerModel $markerModel, Marker $definition, MarkerContext $context) : void
    {
        $iconId = $context->iconId();
        if ($markerModel->icon) {
            $iconId = IconId::fromValue(IntegerDefinitionId::fromValue((int) $markerModel->icon));
        }

        if (!$iconId) {
            return;
        }

        $iconModel = $this->iconRepository->find($iconId->value());
        if (! $iconModel instanceof IconModel || !$this->iconTypes->has($iconModel->type)) {
            return;
        }

        $iconType = $this->iconTypes->get($iconModel->type);
        $icon     = $iconType->createDefinition($iconModel);
        $this->hydrator->hydrate($iconModel, $icon, $context);
        $this->hydrator->hydrate($markerModel, $icon, $context);
        $definition->customizeIcon($icon);
    }
}
