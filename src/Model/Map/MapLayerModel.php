<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Model\Map;

use Cowegis\Bundle\Contao\Model\LayerModel;
use Cowegis\Bundle\Contao\Model\Model;
use Cowegis\Core\Definition\DefinitionId\IntegerDefinitionId;
use Cowegis\Core\Definition\Layer\LayerId;
use RuntimeException;
use Throwable;

/**
 * @property numeric-string|int $pid
 * @property string             $type
 * @property string             $pane
 * @property string             $dataPane
 * @property bool|int|string    $active
 * @property bool|int|string    $initialVisible
 * @property numeric-string|int $layerId
 * @property bool|int|string    $adjustBounds
 */
final class MapLayerModel extends Model
{
    /** @var string */
    protected static $strTable = 'tl_cowegis_map_layer';

    /** @var LayerModel|null */
    private $layer = null;

    /**
     * {@inheritDoc}
     */
    public function __get($key)
    {
        if (isset($this->arrData[$key])) {
            return parent::__get($key);
        }

        try {
            $layer = $this->layerModel();
        } catch (Throwable $exception) {
            return null;
        }

        return $layer->$key;
    }

    /**
     * {@inheritDoc}
     */
    public function __isset($key): bool
    {
        if (parent::__isset($key)) {
            return true;
        }

        try {
            $layer = $this->layerModel();
        } catch (Throwable $exception) {
            return false;
        }

        return isset($layer->$key);
    }

    public function layerId(): LayerId
    {
        return LayerId::fromValue(IntegerDefinitionId::fromValue((int) $this->layerId));
    }

    public function layerModel(): LayerModel
    {
        if ($this->layer === null) {
            $layer = $this->getRelated('layerId');

            /** @psalm-suppress RedundantConditionGivenDocblockType */
            if (! $layer instanceof LayerModel) {
                throw new RuntimeException('No associated layer model found');
            }

            $this->layer = $layer;
        }

        return $this->layer;
    }
}
