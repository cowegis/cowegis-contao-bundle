<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Model\Map;

use Cowegis\Bundle\Contao\Model\LayerModel;
use Cowegis\Bundle\Contao\Model\Model;
use Cowegis\Core\Definition\DefinitionId\IntegerDefinitionId;
use Cowegis\Core\Definition\Layer\LayerId;
use Exception;

/**
 * @property boolean|int|string $active
 * @property boolean|int|string $initialVisible
 */
final class MapLayerModel extends Model
{
    protected static $strTable = 'tl_cowegis_map_layer';

    /** @var LayerModel */
    private $layer;

    public function __get($key)
    {
        if (isset($this->arrData[$key])) {
            return parent::__get($key);
        }

        try {
            $layer = $this->layerModel();
        } catch (Exception $exception) {
            return null;
        }

        return $layer->$key;
    }

    public function __isset($key)
    {
        if (parent::__isset($key)) {
            return true;
        }

        try {
            $layer = $this->layerModel();
        } catch (Exception $exception) {
            return false;
        }

        return isset($layer->$key);
    }

    public function layerId() : LayerId
    {
        return LayerId::fromValue(IntegerDefinitionId::fromValue((int) $this->layerId));
    }

    public function layerModel() : LayerModel
    {
        if ($this->layer === null) {
            $this->layer = $this->getRelated('layerId');
        }

        return $this->layer;
    }
}
