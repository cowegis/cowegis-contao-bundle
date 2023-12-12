<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Layer\Markers\Hydrator;

use Cowegis\Bundle\Contao\Hydrator\Hydrator;
use Cowegis\Bundle\Contao\Map\Icon\IconTypeRegistry;
use Cowegis\Bundle\Contao\Map\Options\ConfigurableOptionsHydrator;
use Cowegis\Bundle\Contao\Model\IconModel;
use Cowegis\Bundle\Contao\Model\IconRepository;
use Cowegis\Bundle\Contao\Model\MarkerModel;
use Cowegis\Bundle\Contao\Provider\MapLayerContext;
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
    /** @var list<string>|array<string,string> */
    protected static array $options = [
        'alt'   => 'alt',
        'title' => 'tooltip',
    ];

    public function __construct(
        private readonly IconTypeRegistry $iconTypes,
        private readonly IconRepository $iconRepository,
    ) {
    }

    public function hydrate(object $data, object $definition, Context $context, Hydrator $hydrator): void
    {
        assert($data instanceof MarkerModel);
        assert($definition instanceof Marker);

        parent::hydrate($data, $definition, $context, $hydrator);

        $definition->changeTitle($data->title);

        if ($context instanceof MarkerContext) {
            $this->hydrateIcon($data, $definition, $context, $hydrator);
        }

        $this->hydratePopup($data, $definition, $context);
        $this->hydrateTooltip($data, $definition, $context);

        if ($data->featureData) {
            $featureData = json_decode($data->featureData, true);
            if (is_array($featureData)) {
                $definition->properties()->merge($featureData);
            }
        }

        if (
            ! ($context instanceof MarkerContext) && ! ($context instanceof MapLayerContext)
            || ! $context->dataPaneId()
        ) {
            return;
        }

        $definition->options()->set('pane', $context->dataPaneId());
    }

    protected function supportsDefinition(object $definition): bool
    {
        return $definition instanceof Marker;
    }

    protected function supportsData(object $data): bool
    {
        return $data instanceof MarkerModel;
    }

    private function hydratePopup(MarkerModel $markerModel, Marker $definition, Context $context): void
    {
        if (! $markerModel->addPopup) {
            return;
        }

        $presetId = null;
        if ($context instanceof MarkerContext) {
            $presetId = $context->popupPresetId();
        }

        if ($markerModel->popup > 0) {
            $presetId = PopupPresetId::fromValue(IntegerDefinitionId::fromValue((int) $markerModel->popup));
        }

        $definition->openPopup(new Popup((string) $markerModel->popupContent, $presetId));
    }

    private function hydrateTooltip(MarkerModel $markerModel, Marker $definition, Context $context): void
    {
        if (! $markerModel->addTooltip) {
            return;
        }

        $presetId = null;
        if ($context instanceof MarkerContext) {
            $presetId = $context->tooltipPresetId();
        }

        if ($markerModel->tooltipPreset > 0) {
            $presetId = TooltipPresetId::fromValue(IntegerDefinitionId::fromValue((int) $markerModel->tooltipPreset));
        }

        $toolTip = new Tooltip((string) $markerModel->tooltipContent, null, $presetId);

        $definition->showTooltip($toolTip);
    }

    private function hydrateIcon(
        MarkerModel $markerModel,
        Marker $definition,
        MarkerContext $context,
        Hydrator $hydrator,
    ): void {
        $iconId = $context->iconId();
        if ($markerModel->icon) {
            $iconId = IconId::fromValue(IntegerDefinitionId::fromValue((int) $markerModel->icon));
        }

        if (! $iconId) {
            return;
        }

        $iconModel = $this->iconRepository->find((int) $iconId->value());
        if (! $iconModel instanceof IconModel || ! $this->iconTypes->has($iconModel->type)) {
            return;
        }

        $iconType = $this->iconTypes->get($iconModel->type);
        $icon     = $iconType->createDefinition($iconModel);
        $hydrator->hydrate($iconModel, $icon, $context, $hydrator);
        $hydrator->hydrate($markerModel, $icon, $context, $hydrator);
        $definition->customizeIcon($icon);
    }
}
