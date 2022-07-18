<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Options;

use Contao\Model;
use Contao\StringUtil;
use Cowegis\Bundle\Contao\Model\Map\MapModel;
use Cowegis\Core\Definition\Map\Map;
use Cowegis\Core\Definition\Options;
use Cowegis\Core\Definition\Point;

use function array_map;
use function assert;
use function count;
use function ucfirst;

final class BoundsOptionsHydrator extends ConfigurableOptionsHydrator
{
    /** @var list<string>|array<string,string> */
    protected static array $options = [
        'dynamic' => 'dynamicLoad',
        'maxZoom' => 'maxZoom',
    ];

    /** @param array<int|string,string> $keys */
    protected function hydrateOptions(Model $model, Options $options, array $keys): void
    {
        parent::hydrateOptions($model, $options, $keys);

        $this->hydrateAdjustOptions($model, $options);
        $this->hydrateBoundsPadding($model, $options);
    }

    protected function determineOptions(object $definition): Options
    {
        assert($definition instanceof Map);

        return $definition->boundsOptions();
    }

    protected function supportsDefinition(object $definition): bool
    {
        return $definition instanceof Map;
    }

    protected function supportsData(object $data): bool
    {
        return $data instanceof MapModel;
    }

    private function hydrateAdjustOptions(Model $model, Options $options): void
    {
        $adjustBounds = StringUtil::deserialize($model->adjustBounds, true);
        foreach ($adjustBounds as $mode) {
            $options->set('adjustAfter' . ucfirst($mode), true);
        }
    }

    private function hydrateBoundsPadding(Model $model, Options $options): void
    {
        $padding = array_map('intval', StringUtil::trimsplit(',', $model->boundsPadding));

        switch (count($padding)) {
            case 1:
                $padding[1] = $padding[0];
                // No break

            case 2:
                $options->set('padding', Point::fromArray([$padding[0], $padding[1]]));
                break;

            case 4:
                $options->set('paddingTopLeft', Point::fromArray([$padding[0], $padding[1]]));
                $options->set('paddingBottomRight', Point::fromArray([$padding[2], $padding[3]]));
        }
    }
}
