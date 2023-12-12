<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\EventListener\Dca;

use Cowegis\Bundle\Contao\Map\Icon\IconTypeRegistry;

final class IconDcaListener
{
    /**
     * Icon type options.
     */
    private IconTypeRegistry $iconTypes;

    /** @param IconTypeRegistry $iconTypes Icon type options. */
    public function __construct(IconTypeRegistry $iconTypes)
    {
        $this->iconTypes = $iconTypes;
    }

    /**
     * Get icon options.
     *
     * @return string[]
     */
    public function iconOptions(): array
    {
        $options = [];
        foreach ($this->iconTypes as $iconType) {
            $options[] = $iconType->name();
        }

        return $options;
    }
}
