<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Action;

use Contao\ContentModel;
use Contao\Model;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class MapContentElementAction extends AbstractMapFragmentAction
{
    public function __invoke(Request $request, ContentModel $model, string $section, array $classes = null): Response
    {
        return $this->renderResponse($request, $model, 'ce_' . $model->type, $section, $classes ?: []);
    }

    protected function getIdentifier(Model $model, ?string $identifier) : string
    {
        if ($identifier === null) {
            return 'map_ce_' . $model->id;
        }

        return $identifier;
    }
}
