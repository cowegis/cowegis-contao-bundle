<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\GeoData;

use Cowegis\Core\Definition\GeoData\GeoData;

final class RawGeoJsonGeoData implements GeoData
{
    public function __construct(private string|null $geoJson)
    {
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'type'   => 'inline',
            'format' => GeoData::FORMAT_GEOJSON,
            'data'   => $this->geoJson,
        ];
    }
}
