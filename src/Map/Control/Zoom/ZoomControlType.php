<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Control\Zoom;

use Cowegis\Bundle\Contao\Map\Control\ControlType;
use Cowegis\Bundle\Contao\Model\ControlModel;
use Cowegis\Core\Definition\Control;
use Cowegis\Core\Definition\Control\ZoomControl;

final class ZoomControlType implements ControlType
{
    public function name() : string
    {
        return 'zoom';
    }

    public function createDefinition(ControlModel $controlModel) : Control
    {
        return new ZoomControl(
            $controlModel->controlId(),
            $controlModel->alias ?: 'control_' . $controlModel->id()
        );
    }
}