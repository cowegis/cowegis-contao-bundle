<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Action;

use Contao\Model;
use Contao\ModuleModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class MapModuleAction extends AbstractMapFragmentAction
{
    public function __invoke(Request $request, ModuleModel $model, string $section, array $classes = null): Response
    {
        return $this->renderResponse($request, $model, 'mod_' . $model->type, $section, $classes ?: []);
    }

    protected function getIdentifier(Model $model, ?string $identifier): string
    {
        if ($identifier === null) {
            return 'map_mod_' . $model->id;
        }

        return $identifier;
    }
}
