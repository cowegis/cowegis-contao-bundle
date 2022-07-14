<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Layer\Markers\Hydrator;

use Cowegis\Bundle\Contao\Provider\MapLayerContext;
use Cowegis\Core\Definition\Icon\IconId;
use Cowegis\Core\Definition\Map\PaneId;
use Cowegis\Core\Definition\Preset\PopupPresetId;
use Cowegis\Core\Definition\Preset\TooltipPresetId;
use Cowegis\Core\Provider\ContextDecorator;

final class MarkerContext extends ContextDecorator
{
    private ?IconId $iconId = null;

    private ?PopupPresetId $popupPresetId = null;

    private ?TooltipPresetId $tooltipPresetId = null;

    private MapLayerContext $inner;

    public function __construct(
        MapLayerContext $inner,
        ?IconId $iconPresetId,
        ?PopupPresetId $popupPresetId,
        ?TooltipPresetId $tooltipPresetId
    ) {
        parent::__construct($inner);

        $this->iconId          = $iconPresetId;
        $this->popupPresetId   = $popupPresetId;
        $this->tooltipPresetId = $tooltipPresetId;
        $this->inner           = $inner;
    }

    public function iconId(): ?IconId
    {
        return $this->iconId;
    }

    public function popupPresetId(): ?PopupPresetId
    {
        return $this->popupPresetId;
    }

    public function tooltipPresetId(): ?TooltipPresetId
    {
        return $this->tooltipPresetId;
    }

    public function paneId(): ?PaneId
    {
        return $this->inner->paneId();
    }

    public function dataPaneId(): ?PaneId
    {
        return $this->inner->dataPaneId();
    }
}
