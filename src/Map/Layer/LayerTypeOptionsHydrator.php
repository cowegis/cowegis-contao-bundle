<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Layer;

trait LayerTypeOptionsHydrator
{
    public function supports(object $data, object $definition): bool
    {
        if (! parent::supports($data, $definition)) {
            return false;
        }

        return $data->type === $this->supportedType();
    }

    abstract protected function supportedType(): string;
}
