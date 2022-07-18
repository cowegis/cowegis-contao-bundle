<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Control\Fullscreen;

use Cowegis\Bundle\Contao\Map\Control\ControlTypeHydrator;

final class FullscreenControlTypeHydrator extends ControlTypeHydrator
{
    /** @var list<string>|array<string,string> */
    protected static array $options = [
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
