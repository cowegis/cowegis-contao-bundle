<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Hydrator\Control;

use Cowegis\Bundle\Contao\Hydrator\Options\ConfigurableOptionsHydrator;
use Cowegis\Bundle\Contao\Model\ControlModel;
use Cowegis\Core\Definition\Control;

abstract class ControlTypeHydrator extends ConfigurableOptionsHydrator
{
    public function supports(object $data, object $definition): bool
    {
        if (! parent::supports($data, $definition)) {
            return false;
        }

        return $data->type === $this->supportedType();
    }

    protected function supportsDefinition(object $definition): bool
    {
        return $definition instanceof Control;
    }

    protected function supportsData(object $data): bool
    {
        return $data instanceof ControlModel;
    }

    abstract protected function supportedType(): string;
}
