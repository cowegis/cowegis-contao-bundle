<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Action;

use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\Model;
use Contao\ModuleModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsFrontendModule('cowegis_map', 'includes', 'ce_cowegis_map')]
final class MapModuleAction extends MapFragmentAction
{
    /** @param list<string>|null $classes */
    public function __invoke(
        Request $request,
        ModuleModel $model,
        string $section,
        array|null $classes = null,
    ): Response {
        return parent::renderAsFrontendModule($request, $model, $section, $classes);
    }

    protected function getIdentifier(Model $model, string|null $identifier): string
    {
        if ($identifier === null) {
            return 'map_mod_' . $model->id;
        }

        return $identifier;
    }
}
