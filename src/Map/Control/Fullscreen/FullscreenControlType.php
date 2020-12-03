<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Control\Fullscreen;

use Cowegis\Bundle\Contao\Map\Control\ControlType;
use Cowegis\Bundle\Contao\Model\ControlModel;
use Cowegis\Core\Definition\Control;
use Cowegis\Core\Definition\Control\FullscreenControl;

final class FullscreenControlType implements ControlType
{
    public function name(): string
    {
        return 'fullscreen';
    }

    public function createDefinition(ControlModel $controlModel): Control
    {
        return new FullscreenControl(
            $controlModel->controlId(),
            $controlModel->alias ?: 'control_' . $controlModel->id()
        );
    }
}
