<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Action;

use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\Model;
use Contao\ModuleModel;
use Netzmacht\Contao\Toolkit\Controller\Fragment\AbstractFrontendModuleController;
use Override;
use Symfony\Component\HttpFoundation\Request;

/** @psalm-suppress PropertyNotSetInConstructor */
#[AsFrontendModule('cowegis_map', 'includes', 'ce_cowegis_map')]
final class MapModuleAction extends AbstractFrontendModuleController
{
    use MapFragment;

    #[Override]
    protected function prepareTemplateData(array $data, Request $request, ModuleModel $model): array
    {
        return $this->buildTemplateData($data, $request, $model);
    }

    #[Override]
    protected function getIdentifier(Model $model, string|null $identifier): string
    {
        if ($identifier === null) {
            return 'map_mod_' . $model->id;
        }

        return $identifier;
    }
}
