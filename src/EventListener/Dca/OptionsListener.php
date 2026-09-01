<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\EventListener\Dca;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;

use function range;

final class OptionsListener
{
    /** @return int[] */
    #[AsCallback('tl_cowegis_map', 'fields.zoom.options')]
    #[AsCallback('tl_cowegis_map', 'fields.minZoom.options')]
    #[AsCallback('tl_cowegis_map', 'fields.maxZoom.options')]
    #[AsCallback('tl_cowegis_map', 'fields.locateMaxZoom.options')]
    #[AsCallback('tl_cowegis_layer', 'fields.minZoom.options')]
    #[AsCallback('tl_cowegis_layer', 'fields.maxZoom.options')]
    #[AsCallback('tl_cowegis_layer', 'fields.maxNativeZoom.options')]
    #[AsCallback('tl_cowegis_layer', 'fields.disableClusteringAtZoom.options')]
    #[AsCallback('tl_cowegis_control', 'fields.zoomControl.options')]
    public function zoomOptions(): array
    {
        return range(1, 20);
    }
}
