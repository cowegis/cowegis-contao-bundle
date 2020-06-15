<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Layer\MarkerCluster;

use Contao\Model;
use Cowegis\Bundle\Contao\Map\Layer\Group\GroupLayerHydrator;
use Cowegis\Bundle\Contao\Model\LayerModel;
use Cowegis\Bundle\Contao\Provider\MapLayerContext;
use Cowegis\Core\Definition\Expression\InlineExpression;
use Cowegis\Core\Definition\Layer\Layer;
use Cowegis\Core\Definition\Layer\MarkerClusterGroup;
use Cowegis\Core\Definition\Options;
use function assert;
use function is_array;
use function json_decode;

final class MarkerClusterGroupHydrator extends GroupLayerHydrator
{
    protected const OPTIONS = [
        'showCoverageOnHover',
        'zoomToBoundsOnClick',
        'removeOutsideVisibleBounds',
        'animateAddingMarkers',
        'spiderfyOnMaxZoom',
        'spiderfyDistanceMultiplier',
        'disableClusteringAtZoom',
        'maxClusterRadius',
        'singleMarkerMode'
    ];

    protected function hydrateLayer(LayerModel $layerModel, Layer $layer, MapLayerContext $context) : void
    {
        parent::hydrateLayer($layerModel, $layer, $context);

        assert($layer instanceof MarkerClusterGroup);

        if ($layerModel->iconCreateFunction) {
            $layer->options()->set(
                'iconCreateFunction',
                $context->callbacks()->add(new InlineExpression($layerModel->iconCreateFunction))
            );
        }
    }

    protected function supportedType() : string
    {
        return 'markerCluster';
    }

    private function hydrateCustomOptions(Model $model, Options $options) : void
    {
        if (! $model->options) {
            return;
        }

        $data = json_decode($model->options, true);
        if (! is_array($data)) {
            return;
        }

        foreach ($data as $key => $value) {
            $options->set($key, $value);
        }
    }
}
