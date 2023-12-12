<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Control\Scale;

use Cowegis\Bundle\Contao\Map\Control\ControlType;
use Cowegis\Bundle\Contao\Model\ControlModel;
use Cowegis\Core\Definition\Control;
use Cowegis\Core\Definition\Control\ScaleControl;

final class ScaleControlType implements ControlType
{
    public function name(): string
    {
        return 'scale';
    }

    public function createDefinition(ControlModel $controlModel): Control
    {
        return new ScaleControl(
            $controlModel->controlId(),
            $controlModel->alias ?: 'control_' . $controlModel->id(),
        );
    }
}
