<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\EventListener;

use Contao\CoreBundle\Event\MenuEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

final class BackendMenuListener
{
    private RouterInterface $router;

    private RequestStack $requestStack;

    public function __construct(RouterInterface $router, RequestStack $requestStack)
    {
        $this->router       = $router;
        $this->requestStack = $requestStack;
    }

    public function onBuild(MenuEvent $event): void
    {
        $factory = $event->getFactory();
        $tree    = $event->getTree();

        if ($tree->getName() !== 'mainMenu') {
            return;
        }

        $contentNode = $tree->getChild('cowegis');
        if ($contentNode === null) {
            return;
        }

        $node = $factory
            ->createItem('my-module')
            ->setUri($this->router->generate('cowegis_contao_backend_api_docs'))
            ->setLabel('API docs')
            ->setLinkAttribute('title', 'Cowegis API docs');

        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $node->setCurrent($request->attributes->get('_backend_module') === 'cowegis-api-docs');
        }

        $contentNode->addChild($node);
    }
}
