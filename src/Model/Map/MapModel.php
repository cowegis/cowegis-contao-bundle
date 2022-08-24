<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Model\Map;

use Cowegis\Bundle\Contao\Model\Model;

/**
 * @property string                $type
 * @property string                $title
 * @property mixed|null            $panes
 * @property string|int|float|null $zoom
 * @property string                $center
 * @property string|list<string>   $assets
 * @property string|list<string>   $assetsOrder
 * @property string|list<string>   $defaultAssets
 * @property string|int|bool       $locate
 * @property string|int            $cacheLifeTime
 * @property string|int|bool       $cache
 */
final class MapModel extends Model
{
    /** @var string */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    protected static $strTable = 'tl_cowegis_map';
}
