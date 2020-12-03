<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Control\Fullscreen;

use Cowegis\Bundle\Contao\Hydrator\Control\ControlTypeHydrator;

final class FullscreenControlTypeHydrator extends ControlTypeHydrator
{
    protected const OPTIONS = [
        'position'              => 'position',
        'title'                 => 'fullscreenTitle',
        'titleCancel'           => 'fullscreenCancelTitle',
        'forcePseudoFullscreen' => 'simulateFullScreen',
        'forceSeparateButton'   => 'separate',
    ];

    protected function supportedType(): string
    {
        return 'fullscreen';
    }
}
