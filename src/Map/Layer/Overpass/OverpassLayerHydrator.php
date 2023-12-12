<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Layer\Overpass;

use Contao\StringUtil;
use Cowegis\Bundle\Contao\Hydrator\Hydrator;
use Cowegis\Bundle\Contao\Map\Layer\LayerTypeHydrator;
use Cowegis\Bundle\Contao\Model\LayerModel;
use Cowegis\Bundle\Contao\Provider\MapLayerContext;
use Cowegis\Core\Definition\DefinitionId\IntegerDefinitionId;
use Cowegis\Core\Definition\Expression\InlineExpression;
use Cowegis\Core\Definition\Icon\IconId;
use Cowegis\Core\Definition\Layer\Layer;

/** @psalm-suppress PropertyNotSetInConstructor - see https://github.com/vimeo/psalm/issues/5062 */
final class OverpassLayerHydrator extends LayerTypeHydrator
{
    /** @var list<string>|array<string,string> */
    protected static array $options = [
        'query'    => 'overpassQuery',
        'minZoom'  => 'minZoom',
        'endpoint' => 'overpassEndpoint',
    ];

    protected function supportedType(): string
    {
        return 'overpass';
    }

    protected function hydrateLayer(
        LayerModel $layerModel,
        Layer $layer,
        MapLayerContext $context,
        Hydrator $hydrator,
    ): void {
        $layer->options()->set('amenityIcons', $this->buildAmenityIcons($layerModel));

        if ($layerModel->pointToLayer) {
            $layer->options()->set(
                'pointToLayer',
                $context->callbacks()->add(new InlineExpression($layerModel->pointToLayer)),
            );
        }

        if ($layerModel->onEachFeature) {
            $layer->options()->set(
                'onEachFeature',
                $context->callbacks()->add(new InlineExpression($layerModel->onEachFeature)),
            );
        }

        if (! $layerModel->overpassPopup) {
            return;
        }

        $layer->options()->set(
            'overpassPopup',
            $context->callbacks()->add(new InlineExpression($layerModel->overpassPopup)),
        );
    }

    /** @return array<string,IconId> */
    private function buildAmenityIcons(LayerModel $layerModel): array
    {
        $amenityIconsConfig = StringUtil::deserialize($layerModel->amenityIcons, true);
        $amenityIconsMap    = [];

        foreach ($amenityIconsConfig as $config) {
            if (! $config['amenity'] || ! $config['icon']) {
                continue;
            }

            $amenityIconsMap[$config['amenity']] = IconId::fromValue(IntegerDefinitionId::fromValue($config['icon']));
        }

        return $amenityIconsMap;
    }
}
