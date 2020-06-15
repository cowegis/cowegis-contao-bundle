<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Icon\Svg;

use Cowegis\Bundle\Contao\Map\Icon\IconTypeHydrator;
use Cowegis\Core\Definition\Icon\SvgIcon;

final class SvgIconHydrator extends IconTypeHydrator
{
    protected const OPTIONS = [
        'backgroundColor',
        'iconColor',
        'className',
        'html' => 'content',
        'className',
    ];

    protected const POINT_OPTIONS = [
        'iconSize',
        'iconAnchor',
        'popupAnchor',
        'tooltipAnchor',
    ];

    protected function supportedType() : string
    {
        return 'svg';
    }

    protected function supportedDefinition() : string
    {
        return SvgIcon::class;
    }
}
