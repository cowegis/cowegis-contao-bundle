<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Layer\Vector;

use Cowegis\Core\Provider\LayerData;
use JsonSerializable;

final class VectorsLayerData implements LayerData, JsonSerializable
{
    public function __construct(private readonly string|null $geoJson)
    {
    }

    public function jsonSerialize(): string|null
    {
        return $this->geoJson;
    }
}
