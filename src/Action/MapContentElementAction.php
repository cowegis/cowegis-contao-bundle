<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Action;

use Contao\ContentModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\Model;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement('cowegis_map', 'includes', 'ce_cowegis_map')]
final class MapContentElementAction extends MapFragmentAction
{
    /** @param list<string>|null $classes */
    public function __invoke(
        Request $request,
        ContentModel $model,
        string $section,
        array|null $classes = null,
    ): Response {
        return parent::renderAsContentElement($request, $model, $section, $classes);
    }

    protected function getIdentifier(Model $model, string|null $identifier): string
    {
        if ($identifier === null) {
            return 'map_ce_' . $model->id;
        }

        return $identifier;
    }
}
