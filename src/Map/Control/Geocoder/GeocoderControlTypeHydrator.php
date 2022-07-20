<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Control\Geocoder;

use Cowegis\Bundle\Contao\Hydrator\Hydrator;
use Cowegis\Bundle\Contao\Map\Control\ControlTypeHydrator;
use Cowegis\Bundle\Contao\Model\ControlModel;
use Cowegis\ContaoGeocoder\Routing\SearchUrlGenerator;
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

    private ?SearchUrlGenerator $searchUrlGenerator;

    public function __construct(SearchUrlGenerator $searchUrlGenerator)
    {
        $this->searchUrlGenerator = $searchUrlGenerator;
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

        if ($this->searchUrlGenerator === null || ! $data->geocoder) {
            return;
        }

        $serviceUrl = $this->searchUrlGenerator->generate(
            ['providerId' => $data->geocoder],
            RouterInterface::ABSOLUTE_URL
        );

        $definition->useGeocoder('cowegis', ['serviceUrl' => $serviceUrl]);
    }
}
