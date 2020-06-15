<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Control;

use Cowegis\Bundle\Contao\Model\ControlModel;
use Cowegis\Core\Definition\Control;

interface ControlType
{
    public function name() : string;

    public function createDefinition(ControlModel $controlModel) : Control;
}
