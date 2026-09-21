<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Layer\Vector;

use Contao\CoreBundle\Cache\CacheTagManager;
use Cowegis\Bundle\Contao\Hydrator\Hydrator;
use Cowegis\Bundle\Contao\Map\GeoData\RawGeoJsonGeoData;
use Cowegis\Bundle\Contao\Map\Layer\LayerTypeHydrator;
use Cowegis\Bundle\Contao\Map\Style\StyleTypeRegistry;
use Cowegis\Bundle\Contao\Model\LayerModel;
use Cowegis\Bundle\Contao\Model\StyleModel;
use Cowegis\Bundle\Contao\Provider\MapLayerContext;
use Cowegis\Core\Definition\Expression\InlineExpression;
use Cowegis\Core\Definition\GeoData\GeoData;
use Cowegis\Core\Definition\GeoData\UriData;
use Cowegis\Core\Definition\Layer\DataLayer;
use Cowegis\Core\Definition\Layer\Layer;
use Override;
use Symfony\Component\Routing\RouterInterface;

use function array_merge;
use function assert;

final class VectorsLayerHydrator extends LayerTypeHydrator
{
    public function __construct(
        private readonly RouterInterface $router,
        CacheTagManager $cacheTagManager,
        private readonly StyleTypeRegistry $styleTypes,
    ) {
        parent::__construct($cacheTagManager);
    }

    #[Override]
    protected function supportedType(): string
    {
        return 'vectors';
    }

    #[Override]
    protected function hydrateLayer(
        LayerModel $layerModel,
        Layer $layer,
        MapLayerContext $context,
        Hydrator $hydrator,
    ): void {
        assert($layer instanceof DataLayer);
        $layer->options()->set('adjustBounds', (bool) $context->mapLayerModel()->adjustBounds);

        if ($layerModel->pointToLayer !== null) {
            $layer->options()->set(
                'pointToLayer',
                $context->callbacks()->add(new InlineExpression($layerModel->pointToLayer)),
            );
        }

        if ($layerModel->onEachFeature !== null) {
            $layer->options()->set(
                'onEachFeature',
                $context->callbacks()->add(new InlineExpression($layerModel->onEachFeature)),
            );
        }

        if ($layerModel->style) {
            $styleModel = StyleModel::findByPk($layerModel->style);
            if ($styleModel instanceof StyleModel) {
                $style = $this->styleTypes->get($styleModel->type)->createDefinition($styleModel);
                $hydrator->hydrate($styleModel, $style, $context, $hydrator);
                $style->apply($layer);
            }
        }

        if ((bool) $layerModel->deferred) {
            $layer->withData(
                new UriData(
                    $this->router->generate(
                        'cowegis_api_layer_data',
                        array_merge(
                            $context->filter()->toQuery()->toArray(),
                            [
                                'type'    => 'vectors',
                                'mapId'   => $context->mapId()->value(),
                                'layerId' => $layer->layerId()->value(),
                                '_locale' => $context->locale(),
                            ],
                        ),
                    ),
                    GeoData::FORMAT_GEOJSON,
                ),
            );

            return;
        }

        $layer->withData(new RawGeoJsonGeoData($layerModel->vectors));
    }
}
