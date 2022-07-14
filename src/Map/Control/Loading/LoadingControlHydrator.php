<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Control\Loading;

use Cowegis\Bundle\Contao\Hydrator\Control\ControlTypeHydrator;
use Cowegis\Bundle\Contao\Hydrator\Hydrator;
use Cowegis\Bundle\Contao\Model\ControlModel;
use Cowegis\Core\Definition\Control\ControlId;
use Cowegis\Core\Definition\Control\LoadingControl;
use Cowegis\Core\Definition\DefinitionId\IntegerDefinitionId;
use Cowegis\Core\Provider\Context;

use function assert;
use function count;
use function is_array;
use function json_decode;

final class LoadingControlHydrator extends ControlTypeHydrator
{
    protected const OPTIONS = [
        'position',
        'separate',
        'delayIndicator',
        'spinjs',
    ];

    public function hydrate(object $data, object $definition, Context $context, Hydrator $hydrator): void
    {
        parent::hydrate($data, $definition, $context, $hydrator);

        assert($data instanceof ControlModel);
        assert($definition instanceof LoadingControl);

        if ($data->spinjs && $data->spin) {
            $config = json_decode($data->spin, true);
            if (is_array($config) && count($config) > 0) {
                $definition->options()->set('spin', $config);
            }
        }

        if (! $data->zoomControl) {
            return;
        }

        $definition->options()->set(
            'zoomControl',
            ControlId::fromValue(IntegerDefinitionId::fromValue((int) $data->zoomControl))
        );
    }

    protected function supportedType(): string
    {
        return 'loading';
    }
}
