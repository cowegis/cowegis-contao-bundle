<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Control\Geocoder;

use Cowegis\Bundle\Contao\Map\Control\ControlType;
use Cowegis\Bundle\Contao\Model\ControlModel;
use Cowegis\Core\Definition\Control;
use Cowegis\Core\Definition\Control\GeocoderControl;

final class GeocoderControlType implements ControlType
{
    public function name(): string
    {
        return 'geocoder';
    }

    public function createDefinition(ControlModel $controlModel): Control
    {
        return new GeocoderControl(
            $controlModel->controlId(),
            $controlModel->alias ?: 'control_' . $controlModel->id()
        );
    }
}
