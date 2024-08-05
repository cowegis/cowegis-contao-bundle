<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Template;
use Netzmacht\Contao\Toolkit\Routing\RequestScopeMatcher;
use Netzmacht\Contao\Toolkit\View\Assets\AssetsManager;

#[AsHook('parseTemplate')]
final class BackendStyleListener
{
    public function __construct(
        private readonly AssetsManager $assetsManager,
        private readonly RequestScopeMatcher $scopeMatcher,
    ) {
    }

    public function __invoke(Template $template): void
    {
        if (! $this->scopeMatcher->isBackendRequest() || $template->getName() !== 'be_main') {
            return;
        }

        $this->assetsManager->addStylesheet('cowegis_contao::css/backend.css');
    }
}
