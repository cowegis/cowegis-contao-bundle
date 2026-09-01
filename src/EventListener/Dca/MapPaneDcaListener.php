<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\EventListener\Dca;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Netzmacht\Contao\Toolkit\Dca\Listener\AbstractListener;
use Override;
use RuntimeException;

use function in_array;
use function sprintf;

final class MapPaneDcaListener extends AbstractListener
{
    // phpcs:ignore SlevomatCodingStandard.TypeHints.ClassConstantTypeHint.MissingNativeTypeHint
    private const RESERVED_PANE_NAMES = [
        'mapPane',
        'tilePane',
        'overlayPane',
        'shadowPane',
        'markerPane',
        'tooltipPane',
        'popupPane',
    ];

    #[Override]
    public static function getName(): string
    {
        return 'tl_cowegis_map_pane';
    }

    /** @param array<string,mixed> $row */
    #[AsCallback('tl_cowegis_map_pane', 'list.sorting.child_record')]
    public function rowLabel(array $row): string
    {
        return sprintf('%s <span class="tl_gray">[%s]</span>', $row['title'], $row['name']);
    }

    #[AsCallback('tl_cowegis_map_pane', 'fields.name.save')]
    public function onSaveName(string $value): string
    {
        if (in_array($value, self::RESERVED_PANE_NAMES, true)) {
            throw new RuntimeException(sprintf('"%s" is a reserved pane name.', $value));
        }

        return $value;
    }
}
