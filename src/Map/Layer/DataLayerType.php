<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Layer;

use Cowegis\Bundle\Contao\Map\Layer\LayerType;

interface DataLayerType extends LayerType
{
    public function dataTable() : string;
}
