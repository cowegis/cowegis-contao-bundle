<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Action;

use Contao\ContentModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\Model;
use Netzmacht\Contao\Toolkit\Controller\Fragment\AbstractContentElementController;
use Netzmacht\Contao\Toolkit\Controller\Fragment\RenderBackendWildcardTrait;
use Override;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/** @psalm-suppress PropertyNotSetInConstructor */
#[AsContentElement('cowegis_map', 'includes')]
final class MapContentElementAction extends AbstractContentElementController
{
    use MapFragment;
    use RenderBackendWildcardTrait;

    /** @SuppressWarnings(PHPMD.UnusedFormalParameter) */
    #[Override]
    protected function preGenerate(FragmentTemplate $template, ContentModel $model, Request $request): Response|null
    {
        if ($this->isBackendScope($request)) {
            return $this->renderBackendWildcard($model, $request);
        }

        return null;
    }

    #[Override]
    protected function prepareTemplateData(array $data, Request $request, ContentModel $model): array
    {
        return $this->buildTemplateData($data, $request, $model);
    }

    #[Override]
    protected function getIdentifier(Model $model, string|null $identifier): string
    {
        if ($identifier === null) {
            return 'map_ce_' . $model->id;
        }

        return $identifier;
    }
}
