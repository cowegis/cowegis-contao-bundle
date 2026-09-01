<?php

declare(strict_types=1);

namespace Cowegis\Bundle\Contao\EventListener\Dca;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
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
    #[AsCallback('tl_cowegis_icon', 'fields.type.options')]
    public function iconOptions(): array
    {
        $options = [];
        foreach ($this->iconTypes as $iconType) {
            $options[] = $iconType->name();
        }

        return $options;
    }
}
