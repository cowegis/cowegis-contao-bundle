<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Icon\Svg;

use Cowegis\Bundle\Contao\Map\Icon\BaseIconType;
use Cowegis\Bundle\Contao\Model\IconModel;
use Cowegis\Core\Definition\Icon\Icon;
use Cowegis\Core\Definition\Icon\SvgIcon;

final class SvgIconType extends BaseIconType
{
    public function name() : string
    {
        return 'svg';
    }

    public function createDefinition(IconModel $iconModel) : Icon
    {
        return new SvgIcon($iconModel->iconId());
    }
}
