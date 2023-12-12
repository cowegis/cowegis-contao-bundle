<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Control\Attribution;

use Cowegis\Bundle\Contao\Map\Control\ControlType;
use Cowegis\Bundle\Contao\Model\ControlModel;
use Cowegis\Core\Definition\Control;
use Cowegis\Core\Definition\Control\AttributionControl;

final class AttributionControlType implements ControlType
{
    public function name(): string
    {
        return 'attribution';
    }

    public function createDefinition(ControlModel $controlModel): Control
    {
        return new AttributionControl(
            $controlModel->controlId(),
            $controlModel->alias ?: 'control_' . $controlModel->id(),
        );
    }
}
