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
 * @property numeric-string|int  $pid
 * @property string              $type
 * @property string              $pane
 * @property string              $dataPane
 * @property bool|int|string     $active
 * @property bool|int|string     $initialVisible
 * @property numeric-string|int  $layerId
 * @property bool|int|string     $adjustBounds
 * @property string|list<string> $filterRules
 */
final class MapLayerModel extends Model
{
    /** @var string */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    protected static $strTable = 'tl_cowegis_map_layer';

    private LayerModel|null $layer = null;

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
        } catch (Throwable) {
            return null;
        }

        return $layer->$key;
    }

    /**
     * {@inheritDoc}
     */
    public function __isset($strKey): bool
    {
        if (parent::__isset($strKey)) {
            return true;
        }

        try {
            $layer = $this->layerModel();
        } catch (Throwable) {
            return false;
        }

        return isset($layer->$strKey);
    }

    public function layerId(): LayerId
    {
        return LayerId::fromValue(IntegerDefinitionId::fromValue((int) $this->layerId));
    }

    public function layerModel(): LayerModel
    {
        if (! $this->layer instanceof LayerModel) {
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
