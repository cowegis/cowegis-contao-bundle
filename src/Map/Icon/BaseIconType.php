<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\Map\Icon;

abstract class BaseIconType implements IconType
{
    public function label(string $label, array $row) : string
    {
        return $label;
    }
}
