<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Layer\Vector;

use Cowegis\Core\Provider\LayerData;
use JsonSerializable;

final class VectorsLayerData implements LayerData, JsonSerializable
{
    private ?string $geoJson;

    public function __construct(?string $geoJson)
    {
        $this->geoJson = $geoJson;
    }

    public function jsonSerialize(): ?string
    {
        return $this->geoJson;
    }
}
