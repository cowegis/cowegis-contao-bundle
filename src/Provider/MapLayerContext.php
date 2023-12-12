<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Provider;

use Cowegis\Bundle\Contao\Model\Map\MapLayerModel;
use Cowegis\Core\Definition\Map\PaneId;
use Cowegis\Core\Provider\Context;
use Cowegis\Core\Provider\ContextDecorator;

final class MapLayerContext extends ContextDecorator
{
    public function __construct(
        Context $context,
        private readonly MapLayerModel $mapLayerModel,
        private readonly PaneId|null $paneId,
        private readonly PaneId|null $dataPaneId,
    ) {
        parent::__construct($context);
    }

    public function paneId(): PaneId|null
    {
        return $this->paneId;
    }

    public function dataPaneId(): PaneId|null
    {
        return $this->dataPaneId;
    }

    public function mapLayerModel(): MapLayerModel
    {
        return $this->mapLayerModel;
    }
}
