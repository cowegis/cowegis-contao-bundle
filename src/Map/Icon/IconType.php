<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Icon;

use Cowegis\Bundle\Contao\Model\IconModel;
use Cowegis\Core\Definition\Icon\Icon;

interface IconType
{
    public function name() : string;

    public function createDefinition(IconModel $iconModel) : Icon;

    public function label(string $label, array $row) : string;

    // TODO: Support icons
    // public function iconUrl() : string;
}
