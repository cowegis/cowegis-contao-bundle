<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Options;

use Contao\Model;
use Cowegis\Bundle\Contao\Hydrator\Hydrator;
use Cowegis\Bundle\Contao\Model\Map\MapModel;
use Cowegis\Core\Definition\Map\Map;
use Cowegis\Core\Definition\Options;
use Cowegis\Core\Provider\Context;

use function assert;
use function is_array;
use function json_decode;

final class MapOptionsHydrator extends ConfigurableOptionsHydrator
{
    protected const OPTIONS = [
        'zoomControl',
        'dragging',
        'touchZoom',
        'scrollWheelZoom',
        'doubleClickZoom',
        'boxZoom',
        'tap',
        'keyboard',
        'trackResize',
        'closePopupOnClick',
        'bounceAtZoomLimits',
        'gestureHandling',
    ];

    protected const CONDITIONAL_OPTIONS = [
        'adjustZoomExtra' => ['minZoom', 'maxZoom', 'zoomSnap', 'zoomDelta'],
        'keyboard' => ['keyboardPanOffset', 'keyboardZoomOffset'],
    ];

    public function hydrate(object $data, object $definition, Context $context, Hydrator $hydrator): void
    {
        assert($data instanceof MapModel);
        assert($definition instanceof Map);

        parent::hydrate($data, $definition, $context, $hydrator);

        $options = $definition->options();

        $this->hydrateCustomOptions($data, $options);
    }

    protected function supportsDefinition(object $definition): bool
    {
        return $definition instanceof Map;
    }

    protected function supportsData(object $data): bool
    {
        return $data instanceof MapModel;
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
