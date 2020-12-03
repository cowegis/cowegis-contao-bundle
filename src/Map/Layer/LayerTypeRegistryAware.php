<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Layer;

interface LayerTypeRegistryAware
{
    public function setRegistry(LayerTypeRegistry $layerTypeRegistry): void;
}
