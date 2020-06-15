<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\EventListener\Dca;

use Netzmacht\Contao\Toolkit\Dca\Listener\AbstractListener;
use RuntimeException;
use function in_array;

final class MapPaneDcaListener extends AbstractListener
{
    private const RESERVED_PANE_NAMES = [
        'mapPane',
        'tilePane',
        'overlayPane',
        'shadowPane',
        'markerPane',
        'tooltipPane',
        'popupPane'
    ];

    /** @var string */
    protected static $name = 'tl_cowegis_map_pane';

    public function rowLabel(array $row) : string
    {
        return sprintf('%s <span class="tl_gray">[%s]</span>', $row['title'], $row['name']);
    }

    public function onSaveName(string $value) : string
    {
        if (in_array($value, self::RESERVED_PANE_NAMES, true)) {
            throw new RuntimeException(sprintf('"%s" is a reserved pane name.', $value));
        }

        return $value;
    }
}
