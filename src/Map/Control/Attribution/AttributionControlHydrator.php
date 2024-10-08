<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Control\Attribution;

use Contao\StringUtil;
use Cowegis\Bundle\Contao\Hydrator\Hydrator;
use Cowegis\Bundle\Contao\Map\Control\ControlTypeHydrator;
use Cowegis\Bundle\Contao\Model\ControlModel;
use Cowegis\Core\Definition\Control\AttributionControl;
use Cowegis\Core\Provider\Context;

use function array_filter;
use function assert;

final class AttributionControlHydrator extends ControlTypeHydrator
{
    /** @var list<string>|array<string,string> */
    protected static array $options = [
        'position',
        'prefix',
    ];

    protected function supportedType(): string
    {
        return 'attribution';
    }

    public function hydrate(object $data, object $definition, Context $context, Hydrator $hydrator): void
    {
        parent::hydrate($data, $definition, $context, $hydrator);

        assert($data instanceof ControlModel);
        assert($definition instanceof AttributionControl);

        $attributions = array_filter(StringUtil::deserialize($data->attributions, true));
        foreach ($attributions as $attribution) {
            $definition->addAttribution($attribution);
        }

        if (! (bool) $data->disableDefault) {
            return;
        }

        $definition->replaceDefault();
    }
}
