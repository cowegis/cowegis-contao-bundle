<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Provider;

use Cowegis\Bundle\Contao\Model\Map\MapLayerModel;
use Cowegis\Core\Definition\Map\PaneId;
use Cowegis\Core\Provider\Context;
use Cowegis\Core\Provider\ContextDecorator;

final class MapLayerContext extends ContextDecorator
{
    /** @var PaneId|null */
    private $dataPaneId;

    /** @var PaneId|null */
    private $pane;

    /**
     * @var MapLayerModel
     */
    private $mapLayerModel;

    public function __construct(Context $context, MapLayerModel $mapLayerModel, ?PaneId $pane, ?PaneId $dataPaneId)
    {
        parent::__construct($context);

        $this->pane          = $pane;
        $this->dataPaneId    = $dataPaneId;
        $this->mapLayerModel = $mapLayerModel;
    }

    public function paneId(): ?PaneId
    {
        return $this->pane;
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
