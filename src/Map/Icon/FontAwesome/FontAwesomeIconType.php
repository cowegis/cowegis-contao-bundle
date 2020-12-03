<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Icon\FontAwesome;

use Cowegis\Bundle\Contao\Map\Icon\BaseIconType;
use Cowegis\Bundle\Contao\Model\IconModel;
use Cowegis\Core\Definition\Icon\FontAwesomeIcon;
use Cowegis\Core\Definition\Icon\Icon;

final class FontAwesomeIconType extends BaseIconType
{
    public function name(): string
    {
        return 'fontAwesome';
    }

    public function createDefinition(IconModel $iconModel): Icon
    {
        return new FontAwesomeIcon($iconModel->iconId());
    }
}
