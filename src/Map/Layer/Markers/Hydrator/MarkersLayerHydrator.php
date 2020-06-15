<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Layer\Markers\Hydrator;

use Cowegis\Bundle\Contao\Hydrator\Layer\LayerTypeHydrator;
use Cowegis\Bundle\Contao\Provider\MapLayerContext;
use Cowegis\Bundle\Contao\Model\LayerModel;
use Cowegis\Bundle\Contao\Provider\LayerDataProvider;
use Cowegis\Core\Definition\Expression\InlineExpression;
use Cowegis\Core\Definition\GeoJson\UriData;
use Cowegis\Core\Definition\Layer\DataLayer;
use Cowegis\Core\Definition\Layer\Layer;
use Cowegis\Core\Provider\LayerData\MarkersLayerData;
use Cowegis\Core\Serializer\Serializer;
use Symfony\Component\Routing\RouterInterface;
use function array_merge;

final class MarkersLayerHydrator extends LayerTypeHydrator
{
   /** @var RouterInterface */
    private $router;

    /** @var LayerDataProvider */
    private $dataProvider;

    /** @var Serializer */
    private $serializer;

    public function __construct(RouterInterface $router, LayerDataProvider $dataProvider, Serializer $serializer)
    {
        $this->router       = $router;
        $this->dataProvider = $dataProvider;
        $this->serializer   = $serializer;
    }

    protected function supportedType() : string
    {
        return 'markers';
    }

    protected function hydrateLayer(LayerModel $layerModel, Layer $layer, MapLayerContext $context) : void
    {
        assert($layer instanceof DataLayer);

        if ($layerModel->pointToLayer) {
            $layer->options()->set(
                'pointToLayer',
                $context->callbacks()->add(new InlineExpression($layerModel->pointToLayer))
            );
        }

        $layer->options()->set('adjustBounds', (bool) $context->mapLayerModel()->adjustBounds);

        if ($layerModel->deferred) {
            $layer->withData(
                new UriData(
                    $this->router->generate(
                        'cowegis_api_layer_data',
                        array_merge(
                            $context->filter()->toQuery()->toArray(),
                            [
                                'type'    => 'markers',
                                'mapId'   => $context->mapId()->value(),
                                'layerId' => $layer->layerId()->value(),
                                '_locale' => $GLOBALS['TL_LANGUAGE'],
                            ]
                        )
                    )
                )
            );

            return;
        }

        $layerData = $this->dataProvider->findLayerData($layerModel, $context);
        if ($layerData instanceof MarkersLayerData) {
            $layer->withData($this->serializer->serialize($layerData));
        }
    }
}