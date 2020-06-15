<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Hydrator\Presets;

use Contao\StringUtil;
use Cowegis\Core\Provider\Context;
use Cowegis\Bundle\Contao\Hydrator\Options\ConfigurableOptionsHydrator;
use Cowegis\Bundle\Contao\Model\TooltipModel;
use Cowegis\Core\Definition\Point;
use Cowegis\Core\Definition\Preset\TooltipPreset;
use function array_map;
use function assert;

final class TooltipPresetHydrator extends ConfigurableOptionsHydrator
{
    protected const OPTIONS = [
        'attribution',
        'className',
        'pane',
        'direction',
        'permanent',
        'sticky',
        'interactive',
        'opacity',
    ];

    public function hydrate(object $data, object $definition, Context $context) : void
    {
        assert($data instanceof TooltipModel);
        assert($definition instanceof TooltipPreset);

        parent::hydrate($data, $definition, $context);

        if ($data->offset) {
            $definition->options()->set(
                'offset',
                Point::fromArray(array_map('intval', StringUtil::trimsplit(',', $data->offset)))
            );
        }
    }

    protected function supportsDefinition(object $definition) : bool
    {
        return $definition instanceof TooltipPreset;
    }

    protected function supportsData(object $data) : bool
    {
        return $data instanceof TooltipModel;
    }
}
