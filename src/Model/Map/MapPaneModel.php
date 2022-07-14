<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Model\Map;

use Cowegis\Bundle\Contao\Model\Model;

/**
 * @property string                  $type
 * @property string                  $name
 * @property numeric-string|int|null $zIndex
 * @property string                  $pointerEvents
 */
final class MapPaneModel extends Model
{
    /** @var string */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    protected static $strTable = 'tl_cowegis_map_pane';
}
