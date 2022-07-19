<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Model;

use Cowegis\Core\Definition\Control\ControlId;
use Cowegis\Core\Definition\DefinitionId\IntegerDefinitionId;

/**
 * @property string                   $type
 * @property string                   $alias
 * @property string|list<string>|null $attributions
 * @property numeric-string|int|bool  $disableDefault
 * @property string|null              $nameFunction
 * @property numeric-string|int|bool  $sortLayers
 * @property string|null              $sortFunction
 * @property numeric-string|int|bool  $spinjs
 * @property string|null              $spin
 * @property numeric-string|int       $zoomControl
 * @property string                   $geocoder
 */
class ControlModel extends Model
{
    /** @var string */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    protected static $strTable = 'tl_cowegis_control';

    public function controlId(): ControlId
    {
        return ControlId::fromValue(IntegerDefinitionId::fromValue($this->id()));
    }
}
