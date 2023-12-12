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
    public function __construct(
        private readonly MapLayerContext $inner,
        private readonly IconId|null $iconId = null,
        private readonly PopupPresetId|null $popupPresetId = null,
        private readonly TooltipPresetId|null $tooltipPresetId = null,
    ) {
        parent::__construct($this->inner);
    }

    public function iconId(): IconId|null
    {
        return $this->iconId;
    }

    public function popupPresetId(): PopupPresetId|null
    {
        return $this->popupPresetId;
    }

    public function tooltipPresetId(): TooltipPresetId|null
    {
        return $this->tooltipPresetId;
    }

    public function paneId(): PaneId|null
    {
        return $this->inner->paneId();
    }

    public function dataPaneId(): PaneId|null
    {
        return $this->inner->dataPaneId();
    }
}
