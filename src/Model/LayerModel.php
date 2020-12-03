<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Model;

use Cowegis\Core\Definition\DefinitionId\IntegerDefinitionId;
use Cowegis\Core\Definition\Layer\LayerId;

/**
 * @property string            $type
 * @property string            $alias
 * @property string            $tileUrl
 * @property string|null       $pointToLayer
 * @property string|null       $onEachFeature
 * @property string|null       $overpassPopup
 * @property string|array|null $amenityIcons
 */
class LayerModel extends Model
{
    /** @var string */
    protected static $strTable = 'tl_cowegis_layer';

    public function layerId(): LayerId
    {
        return LayerId::fromValue(IntegerDefinitionId::fromValue($this->id()));
    }
}
