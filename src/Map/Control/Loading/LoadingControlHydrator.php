<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Control\Loading;

use Cowegis\Bundle\Contao\Hydrator\Control\ControlTypeHydrator;
use Cowegis\Bundle\Contao\Model\ControlModel;
use Cowegis\Core\Definition\Control\ControlId;
use Cowegis\Core\Definition\Control\LoadingControl;
use Cowegis\Core\Definition\DefinitionId\IntegerDefinitionId;
use Cowegis\Core\Provider\Context;
use function assert;
use function is_array;
use function json_decode;

final class LoadingControlHydrator extends ControlTypeHydrator
{
    protected const OPTIONS = [
        'position',
        'separate',
        'delayIndicator',
        'spinjs'
    ];

    public function hydrate(object $controlModel, object $control, Context $context) : void
    {
        parent::hydrate($controlModel, $control, $context);

        assert($controlModel instanceof ControlModel);
        assert($control instanceof LoadingControl);

        if ($controlModel->spinjs && $controlModel->spin) {
            $config = json_decode($controlModel->spin, true);
            if (is_array($config) && count($config) > 0) {
                $control->options()->set('spin', $config);
            }
        }

        if ($controlModel->zoomControl) {
            $control->options()->set(
                'zoomControl',
                ControlId::fromValue(IntegerDefinitionId::fromValue((int) $controlModel->zoomControl))
            );
        }
    }

    protected function supportedType() : string
    {
        return 'loading';
    }
}
