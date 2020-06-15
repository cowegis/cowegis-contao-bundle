<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\EventListener;

use Contao\CoreBundle\Event\MenuEvent;
use Cowegis\Bundle\Contao\Action\Backend\DocsAction;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

final class BackendMenuListener
{
    private $router;
    private $requestStack;

    public function __construct(RouterInterface $router, RequestStack $requestStack)
    {
        $this->router = $router;
        $this->requestStack = $requestStack;
    }

    public function onBuild(MenuEvent $event): void
    {
        $factory = $event->getFactory();
        $tree = $event->getTree();

        if ('mainMenu' !== $tree->getName()) {
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
            ->setLinkAttribute('title', 'Cowegis API docs')
            ->setCurrent($this->requestStack->getCurrentRequest()->get('_backend_module') === 'cowegis-api-docs')
        ;

        $contentNode->addChild($node);
    }
}
