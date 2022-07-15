<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Action;

use Contao\Model;

final class MapContentElementAction extends MapFragmentAction
{
    protected function getIdentifier(Model $model, ?string $identifier): string
    {
        if ($identifier === null) {
            return 'map_ce_' . $model->id;
        }

        return $identifier;
    }
}
