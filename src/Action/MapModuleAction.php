<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Action;

use Contao\Model;

final class MapModuleAction extends MapFragmentAction
{
    protected function getIdentifier(Model $model, string|null $identifier): string
    {
        if ($identifier === null) {
            return 'map_mod_' . $model->id;
        }

        return $identifier;
    }
}
