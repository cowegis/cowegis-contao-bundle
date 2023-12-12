<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\EventListener;

use Contao\CoreBundle\Event\MenuEvent;
use Symfony\Component\HttpFoundation\RequestStack;

final class BackendMenuListener
{
    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function onBuild(MenuEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return;
        }

        $tree = $event->getTree();

        if ($tree->getName() !== 'mainMenu') {
            return;
        }

        $contentNode = $tree->getChild('cowegis');
        if ($contentNode === null) {
            return;
        }

        $mapNode = $contentNode->getChild('cowegis_map');
        if ($mapNode === null) {
            return;
        }

        if ($request->attributes->get('_backend_module') !== 'cowegis-api-docs') {
            return;
        }

        $mapNode->setCurrent(true);
    }
}
