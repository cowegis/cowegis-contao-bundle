<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Provider;

use Cowegis\Bundle\Contao\Model\Map\MapLayerModel;
use Cowegis\Core\Definition\Map\PaneId;
use Cowegis\Core\Provider\Context;
use Cowegis\Core\Provider\ContextDecorator;

final class MapLayerContext extends ContextDecorator
{
    private ?PaneId $dataPaneId = null;

    private ?PaneId $paneId = null;

    private MapLayerModel $mapLayerModel;

    public function __construct(Context $context, MapLayerModel $mapLayerModel, ?PaneId $paneId, ?PaneId $dataPaneId)
    {
        parent::__construct($context);

        $this->paneId        = $paneId;
        $this->dataPaneId    = $dataPaneId;
        $this->mapLayerModel = $mapLayerModel;
    }

    public function paneId(): ?PaneId
    {
        return $this->paneId;
    }

    public function dataPaneId(): ?PaneId
    {
        return $this->dataPaneId;
    }

    public function mapLayerModel(): MapLayerModel
    {
        return $this->mapLayerModel;
    }
}
