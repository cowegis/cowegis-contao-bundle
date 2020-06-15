<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Control\Attribution;

use Contao\StringUtil;
use Cowegis\Bundle\Contao\Hydrator\Control\ControlTypeHydrator;
use Cowegis\Bundle\Contao\Model\ControlModel;
use Cowegis\Core\Definition\Control\AttributionControl;
use Cowegis\Core\Provider\Context;
use function array_filter;
use function assert;

final class AttributionControlHydrator extends ControlTypeHydrator
{
    protected const OPTIONS = [
        'position',
        'prefix'
    ];

    protected function supportedType() : string
    {
        return 'attribution';
    }

    public function hydrate(object $controlModel, object $definition, Context $context) : void
    {
        parent::hydrate($controlModel, $definition, $context);

        assert($controlModel instanceof ControlModel);
        assert($definition instanceof AttributionControl);

        $attributions = array_filter(StringUtil::deserialize($controlModel->attributions, true));
        foreach ($attributions as $attribution) {
            $definition->addAttribution($attribution);
        }

        if ($controlModel->disableDefault) {
            $definition->replaceDefault();
        }
    }
}