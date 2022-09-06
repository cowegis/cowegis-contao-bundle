<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Model;

use Cowegis\Core\Definition\DefinitionId\IntegerDefinitionId;
use Cowegis\Core\Definition\Layer\LayerId;

/**
 * @property string|array|null       $amenityIcons
 * @property string                  $alias
 * @property numeric-string|int|bool $deferred
 * @property string                  $file
 * @property string                  $fileFormat
 * @property string                  $groupType
 * @property numeric-string|int      $icon
 * @property string|null             $iconCreateFunction
 * @property string|null             $onEachFeature
 * @property string|null             $overpassPopup
 * @property string|null             $pointToLayer
 * @property string|null             $popup
 * @property numeric-string|int      $reference
 * @property string                  $title
 * @property string                  $tileUrl
 * @property string|null             $tooltip
 * @property string                  $type
 * @property string|null             $vectors
 * @property string|int              $cacheLifeTime
 * @property string|int|bool         $cache
 */
class LayerModel extends Model
{
    /** @var string */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    protected static $strTable = 'tl_cowegis_layer';

    public function layerId(): LayerId
    {
        return LayerId::fromValue(IntegerDefinitionId::fromValue($this->id()));
    }
}
