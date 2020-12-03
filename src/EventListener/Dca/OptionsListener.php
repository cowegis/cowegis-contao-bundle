<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\EventListener\Dca;

use function range;

final class OptionsListener
{
    /** @return int[] */
    public function zoomOptions(): array
    {
        return range(1, 20);
    }
}
