<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Control\Layers;

use Cowegis\Bundle\Contao\Map\Control\ControlType;
use Cowegis\Bundle\Contao\Model\ControlModel;
use Cowegis\Core\Definition\Control;
use Cowegis\Core\Definition\Control\LayersControl;

final class LayersControlType implements ControlType
{
    public function name() : string
    {
        return 'layers';
    }

    public function createDefinition(ControlModel $controlModel) : Control
    {
        return new LayersControl(
            $controlModel->controlId(),
            $controlModel->alias ?: 'control_' . $controlModel->id()
        );
    }
}
