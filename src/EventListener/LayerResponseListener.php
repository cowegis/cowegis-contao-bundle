<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\EventListener;

use Contao\Model;
use Cowegis\Bundle\Api\Event\LayerResponseEvent;
use Cowegis\Bundle\Contao\Model\LayerRepository;

final class LayerResponseListener
{
    public function __construct(private readonly LayerRepository $layers)
    {
    }

    public function __invoke(LayerResponseEvent $event): void
    {
        $layerId    = $event->layerId();
        $layerModel = $this->layers->find((int) $layerId->value());

        if (! $layerModel instanceof Model || ! (bool) $layerModel->deferred || ! (bool) $layerModel->cache) {
            return;
        }

        $event->response()
            ->setPublic()
            ->setMaxAge((int) $layerModel->cacheLifeTime);
    }
}
