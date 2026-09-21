<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\EventListener;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Netzmacht\Contao\Toolkit\View\Assets\AssetsManager;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;

#[AsEventListener(priority: -128)]
final class BackendStyleListener
{
    public function __construct(
        private readonly AssetsManager $assetsManager,
        private readonly ScopeMatcher $scopeMatcher,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (! $this->scopeMatcher->isBackendRequest($event->getRequest())) {
            return;
        }

        $this->assetsManager->addStylesheet('bundles/cowegiscontao/css/backend.css', static: false);
    }
}
