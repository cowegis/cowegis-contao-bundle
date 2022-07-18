<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Control\Geocoder;

use Cowegis\Bundle\Contao\Map\Control\ControlTypeHydrator;

final class GeocoderControlTypeHydrator extends ControlTypeHydrator
{
    /** @var list<string>|array<string,string> */
    protected static array $options = [
        'query'              => 'geocodeQuery',
        'position'           => 'position',
        'collapsed'          => 'collapsed',
        'defaultMarkGeocode' => 'defaultMarkGeocode',
        'errorMessage'       => 'errorMessage',
        'expand'             => 'expand',
        'iconLabel'          => 'iconLabel',
        'placeholder'        => 'placeholder',
        'queryMinLength'     => 'queryMinLength',
        'showResultIcons'    => 'showResultIcons',
        'showUniqueResult'   => 'showUniqueResult',
        'suggestMinLength'   => 'suggestMinLength',
        'suggestTimeout'     => 'suggestTimeout',
    ];

    protected function supportedType(): string
    {
        return 'geocoder';
    }
}
