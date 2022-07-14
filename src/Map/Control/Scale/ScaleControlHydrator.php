<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Control\Scale;

use Cowegis\Bundle\Contao\Map\Control\ControlTypeHydrator;

final class ScaleControlHydrator extends ControlTypeHydrator
{
    protected const OPTIONS = [
        'position',
        'maxWidth',
        'metric',
        'imperial',
        'updateWhenIdle',
    ];

    protected function supportedType(): string
    {
        return 'scale';
    }
}
