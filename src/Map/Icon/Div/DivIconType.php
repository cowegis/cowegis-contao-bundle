<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Icon\Div;

use Cowegis\Bundle\Contao\Map\Icon\BaseIconType;
use Cowegis\Bundle\Contao\Model\IconModel;
use Cowegis\Core\Definition\Icon\DivIcon;
use Cowegis\Core\Definition\Icon\Icon;

final class DivIconType extends BaseIconType
{
    public function name() : string
    {
        return 'div';
    }

    public function createDefinition(IconModel $iconModel) : Icon
    {
        return new DivIcon($iconModel->iconId());
    }
}
