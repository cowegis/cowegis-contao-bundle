<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Layer\MarkerCluster;

use Contao\Model;
use Cowegis\Bundle\Contao\Hydrator\Hydrator;
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
    /** @var list<string>|array<string,string> */
    protected static array $options = [
        'showCoverageOnHover',
        'zoomToBoundsOnClick',
        'removeOutsideVisibleBounds',
        'animateAddingMarkers',
        'spiderfyOnMaxZoom',
        'spiderfyDistanceMultiplier',
        'disableClusteringAtZoom',
        'maxClusterRadius',
        'singleMarkerMode',
    ];

    protected function hydrateLayer(
        LayerModel $layerModel,
        Layer $layer,
        MapLayerContext $context,
        Hydrator $hydrator,
    ): void {
        parent::hydrateLayer($layerModel, $layer, $context, $hydrator);

        assert($layer instanceof MarkerClusterGroup);

        if ($layerModel->iconCreateFunction === null) {
            return;
        }

        $layer->options()->set(
            'iconCreateFunction',
            $context->callbacks()->add(new InlineExpression($layerModel->iconCreateFunction)),
        );

        $this->hydrateCustomOptions($layerModel, $layer->options());
    }

    protected function supportedType(): string
    {
        return 'markerCluster';
    }

    private function hydrateCustomOptions(Model $model, Options $options): void
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
