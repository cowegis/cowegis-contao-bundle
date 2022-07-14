<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Layer;

use Cowegis\Bundle\Contao\Hydrator\Hydrator;
use Cowegis\Bundle\Contao\Map\Options\ConfigurableOptionsHydrator;
use Cowegis\Bundle\Contao\Model\LayerModel;
use Cowegis\Bundle\Contao\Provider\MapLayerContext;
use Cowegis\Core\Definition\Layer\Layer;
use Cowegis\Core\Provider\Context;

use function assert;

abstract class LayerTypeHydrator extends ConfigurableOptionsHydrator
{
    public function supports(object $data, object $definition): bool
    {
        if (! $definition instanceof Layer) {
            return false;
        }

        if (! $data instanceof LayerModel) {
            return false;
        }

        return $data->type === $this->supportedType();
    }

    public function hydrate(object $data, object $definition, Context $context, Hydrator $hydrator): void
    {
        assert($data instanceof LayerModel);
        assert($definition instanceof Layer);
        assert($context instanceof MapLayerContext);

        parent::hydrate($data, $definition, $context, $hydrator);

        $definition->changeTitle($data->title);
        $this->hydratePane($definition, $context);
        $this->hydrateLayer($data, $definition, $context, $hydrator);
    }

    abstract protected function hydrateLayer(
        LayerModel $layerModel,
        Layer $layer,
        MapLayerContext $context,
        Hydrator $hydrator
    ): void;

    abstract protected function supportedType(): string;

    private function hydratePane(Layer $definition, MapLayerContext $context): void
    {
        $paneId = $context->paneId();
        if ($paneId === null) {
            return;
        }

        $definition->options()->set('pane', $paneId);
    }
}
