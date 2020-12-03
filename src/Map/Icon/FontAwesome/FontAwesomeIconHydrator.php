<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Icon\FontAwesome;

use Cowegis\Bundle\Contao\Map\Icon\IconTypeHydrator;
use Cowegis\Bundle\Contao\Model\MarkerModel;
use Cowegis\Core\Definition\Icon\FontAwesomeIcon;
use Cowegis\Core\Definition\Icon\Icon;

final class FontAwesomeIconHydrator extends IconTypeHydrator
{
    protected const OPTIONS = [
        'bgColor'   => 'backgroundColor',
        'color'     => 'iconColor',
        'className' => 'className',
        'faIconSet' => 'iconSet',
        'icon'      => 'icon',
    ];

    protected const POINT_OPTIONS = [
        'iconSize',
        'iconAnchor',
        'popupAnchor',
        'tooltipAnchor',
    ];

    protected function supportedType(): string
    {
        return 'fontAwesome';
    }

    protected function customizeForMarker(MarkerModel $markerModel, Icon $icon): void
    {
        if (! $markerModel->markerSymbol) {
            return;
        }

        $icon->options()->set('icon', $markerModel->markerSymbol);
    }

    protected function supportedDefinition(): string
    {
        return FontAwesomeIcon::class;
    }
}
