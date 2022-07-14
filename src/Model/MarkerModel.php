<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Model;

/**
 * @property string                   $alias
 * @property numeric-string|int|bool  $addPopup
 * @property numeric-string|int|bool  $addTooltip
 * @property string                   $title
 * @property string|null              $featureData
 * @property string                   $markerSymbol
 * @property numeric-string|int|bool  $popup
 * @property string|null              $popupContent
 * @property numeric-string|int|bool  $tooltipPreset
 * @property numeric-string|int       $icon
 * @property numeric-string|int|float $latitude
 * @property numeric-string|int|float $longitude
 * @property numeric-string|int|float $altitude
 * @property string|null              $tooltipContent
 */
class MarkerModel extends Model
{
    /** @var string */
    protected static $strTable = 'tl_cowegis_marker';
}
