<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\EventListener;

use Cowegis\Bundle\Api\Event\MapResponseEvent;
use Cowegis\Bundle\Contao\Model\Map\MapRepository;

final class MapResponseListener
{
    public function __construct(private readonly MapRepository $maps)
    {
    }

    public function __invoke(MapResponseEvent $event): void
    {
        $mapId    = $event->definition()->mapId();
        $mapModel = $this->maps->find((int) $mapId->value());

        if (! $mapModel || ! $mapModel->cache) {
            return;
        }

        $event->response()
            ->setPublic()
            ->setMaxAge((int) $mapModel->cacheLifeTime);
    }
}
