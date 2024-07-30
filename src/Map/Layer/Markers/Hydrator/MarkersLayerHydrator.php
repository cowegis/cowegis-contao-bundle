<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Layer\Markers\Hydrator;

use Cowegis\Bundle\Contao\Hydrator\Hydrator;
use Cowegis\Bundle\Contao\Map\Layer\LayerTypeHydrator;
use Cowegis\Bundle\Contao\Model\LayerModel;
use Cowegis\Bundle\Contao\Provider\LayerDataProvider;
use Cowegis\Bundle\Contao\Provider\MapLayerContext;
use Cowegis\Core\Definition\Expression\InlineExpression;
use Cowegis\Core\Definition\GeoData\UriData;
use Cowegis\Core\Definition\Layer\DataLayer;
use Cowegis\Core\Definition\Layer\Layer;
use Cowegis\Core\Provider\LayerData\MarkersLayerData;
use Cowegis\Core\Serializer\Serializer;
use Netzmacht\Contao\Toolkit\Response\ResponseTagger;
use Symfony\Component\Routing\RouterInterface;

use function array_merge;
use function assert;

final class MarkersLayerHydrator extends LayerTypeHydrator
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly LayerDataProvider $dataProvider,
        private readonly Serializer $serializer,
        ResponseTagger $responseTagger,
    ) {
        parent::__construct($responseTagger);
    }

    protected function supportedType(): string
    {
        return 'markers';
    }

    protected function hydrateLayer(
        LayerModel $layerModel,
        Layer $layer,
        MapLayerContext $context,
        Hydrator $hydrator,
    ): void {
        assert($layer instanceof DataLayer);

        if ($layerModel->pointToLayer !== null) {
            $layer->options()->set(
                'pointToLayer',
                $context->callbacks()->add(new InlineExpression($layerModel->pointToLayer)),
            );
        }

        $layer->options()->set('adjustBounds', (bool) $context->mapLayerModel()->adjustBounds);

        if ($layerModel->deferred === true) {
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
                                '_locale' => $context->locale(),
                            ],
                        ),
                    ),
                    'geojson',
                ),
            );

            return;
        }

        $layerData = $this->dataProvider->findLayerData($layerModel, $context);
        if (! ($layerData instanceof MarkersLayerData)) {
            return;
        }

        $layer->withData($this->serializer->serialize($layerData));
    }
}
