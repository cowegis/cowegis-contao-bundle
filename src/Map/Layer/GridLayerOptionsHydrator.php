<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Layer;

use Cowegis\Bundle\Contao\Hydrator\Hydrator;
use Cowegis\Core\Definition\DefinitionId\IntegerDefinitionId;
use Cowegis\Core\Definition\Layer\GridLayer;
use Cowegis\Core\Definition\Map\PaneId;
use Cowegis\Core\Provider\Context;

use function assert;

final class GridLayerOptionsHydrator extends LayerOptionsHydrator
{
    /** @var list<string>|array<string,string> */
    protected static array $options = [
        'tileSize',
        'opacity',
        'updateWhenIdle',
        'updateWhenZooming',
        'updateInterval',
        'zIndex',
        'bounds',
        'maxNativeZoom',
        'minNativeZoom',
        'noWrap',
        'className',
        'keepBuffer',
    ];

    public function hydrate(object $data, object $definition, Context $context, Hydrator $hydrator): void
    {
        parent::hydrate($data, $definition, $context, $hydrator);

        assert($definition instanceof GridLayer);

        if (! $data->pane) {
            return;
        }

        $definition->options()->set('pane', new PaneId(new IntegerDefinitionId((int) $data->pane)));
    }

    public function supports(object $data, object $definition): bool
    {
        if (! parent::supports($data, $definition)) {
            return false;
        }

        return $definition instanceof GridLayer;
    }
}
