<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Control\Geocoder;

use Cowegis\Bundle\Contao\Hydrator\Hydrator;
use Cowegis\Bundle\Contao\Map\Control\ControlTypeHydrator;
use Cowegis\Bundle\Contao\Model\ControlModel;
use Cowegis\ContaoGeocoder\Provider\Geocoder;
use Cowegis\Core\Definition\Control\GeocoderControl;
use Cowegis\Core\Provider\Context;
use Symfony\Component\Routing\RouterInterface;

use function assert;

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

    private RouterInterface $router;

    private ?Geocoder $geocoder;

    public function __construct(RouterInterface $router, ?Geocoder $geocoder)
    {
        $this->geocoder = $geocoder;
        $this->router   = $router;
    }

    protected function supportedType(): string
    {
        return 'geocoder';
    }

    public function hydrate(object $data, object $definition, Context $context, Hydrator $hydrator): void
    {
        parent::hydrate($data, $definition, $context, $hydrator);

        assert($data instanceof ControlModel);
        assert($definition instanceof GeocoderControl);

        if ($this->geocoder === null || ! $data->geocoder) {
            return;
        }

        // leaflet-control-geocoder nominatim
        $serviceUrl = $this->router->generate(
            'cowegis_geocoder_provider_search',
            ['providerId' => $data->geocoder],
            RouterInterface::ABSOLUTE_URL
        );

        $definition->useGeocoder('cowegis', ['serviceUrl' => $serviceUrl]);
    }
}
