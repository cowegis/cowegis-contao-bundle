<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Layer;

interface DataLayerType extends LayerType
{
    public function dataTable(): string;
}
