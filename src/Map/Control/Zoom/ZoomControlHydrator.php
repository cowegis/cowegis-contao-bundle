<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Control\Zoom;

use Cowegis\Bundle\Contao\Hydrator\Control\ControlTypeHydrator;
use Cowegis\Bundle\Contao\Model\ControlModel;
use Cowegis\Core\Definition\Control\ZoomControl;
use Cowegis\Core\Provider\Context;

use function assert;

final class ZoomControlHydrator extends ControlTypeHydrator
{
    protected const OPTIONS = [
        'position',
        'zoomInText',
        'zoomInTitle',
        'zoomOutText',
        'zoomOutTitle',
    ];

    public function hydrate(object $data, object $definition, Context $context): void
    {
        parent::hydrate($data, $definition, $context);

        assert($data instanceof ControlModel);
        assert($definition instanceof ZoomControl);

        if (! $data->disableDefault) {
            return;
        }

        $definition->replaceDefault();
    }

    protected function supportedType(): string
    {
        return 'zoom';
    }
}
