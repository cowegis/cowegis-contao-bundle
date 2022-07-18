<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Layer;

final class LayerObjectOptionsHydrator extends LayerOptionsHydrator
{
    /** @var list<string>|array<string,string> */
    protected static array $options = ['attribution'];
}
